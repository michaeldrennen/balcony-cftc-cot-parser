<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MichaelDrennen\Balcony\CftcCotParser\DTO\CotPositionDTO;
use MichaelDrennen\Balcony\CftcCotParser\Exceptions\BalconyClientException;

final class Client
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $config->timeoutSeconds,
            'http_errors' => false,
        ]);
    }

    /**
     * @return CotPositionDTO[]
     */
    public function fetchReports(): array
    {
        try {
            $response = $this->http->request('GET', $this->config->url());
        } catch (GuzzleException $e) {
            throw BalconyClientException::transport($e->getMessage(), $e);
        }

        $status = $response->getStatusCode();

        if ($status === 429) {
            $retryHeader = $response->getHeaderLine('Retry-After');
            throw BalconyClientException::rateLimited(is_numeric($retryHeader) ? (int) $retryHeader : null);
        }

        if ($status >= 400) {
            throw BalconyClientException::httpError($status, (string) $response->getBody());
        }

        return $this->parse((string) $response->getBody());
    }

    /**
     * Parse the CFTC comma-delimited TFF text. Header and malformed lines are skipped,
     * and only rows whose contract code is tracked are returned.
     *
     * Column order: name, as-of date (YYMMDD), contract code, open interest,
     * dealer long/short, asset manager long/short, leveraged funds long/short.
     *
     * @return CotPositionDTO[]
     */
    public function parse(string $text): array
    {
        $text = ltrim($text, "\xEF\xBB\xBF");
        $tracked = array_map('trim', $this->config->trackedCodes);
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = array_map('trim', str_getcsv($line));

            if (count($cells) < 10) {
                continue;
            }

            $code = $cells[2];

            if (!in_array($code, $tracked, true)) {
                continue;
            }

            $reportDate = self::parseYymmdd($cells[1]);
            $numbers = array_map([self::class, 'parseInt'], array_slice($cells, 3, 7));

            if ($reportDate === null || in_array(null, $numbers, true)) {
                continue;
            }

            $rows[] = new CotPositionDTO(
                reportDate: $reportDate,
                contractCode: $code,
                contractName: $cells[0],
                openInterest: $numbers[0],
                dealerLong: $numbers[1],
                dealerShort: $numbers[2],
                assetManagerLong: $numbers[3],
                assetManagerShort: $numbers[4],
                leveragedFundsLong: $numbers[5],
                leveragedFundsShort: $numbers[6],
            );
        }

        return $rows;
    }

    public static function parseInt(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = str_replace(',', '', trim($raw));

        return preg_match('/^-?\d+$/', $trimmed) === 1 ? (int) $trimmed : null;
    }

    public static function parseYymmdd(string $raw): ?DateTimeImmutable
    {
        if (preg_match('/^\d{6}$/', $raw) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Ymd', '20' . $raw);

        return ($date !== false && $date->format('Ymd') === '20' . $raw) ? $date : null;
    }
}
