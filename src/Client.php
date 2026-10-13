<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MichaelDrennen\Balcony\CftcCotParser\DTO\CotPositionDTO;
use MichaelDrennen\Balcony\CftcCotParser\Exceptions\BalconyClientException;
use ZipArchive;

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

        $body = (string) $response->getBody();

        return $this->parse(str_starts_with($body, "PK\x03\x04") ? self::unzipFirstEntry($body) : $body);
    }

    /**
     * Parse the CFTC comma-delimited TFF futures-only text (weekly FinFutWk.txt or the
     * FinFutYY.txt inside a yearly archive). Header and malformed lines are skipped,
     * and only rows whose contract code is tracked are returned.
     *
     * Columns used (0-based): 0 name, 1 as-of date (YYMMDD), 3 contract code, 7 open interest,
     * 8/9 dealer long/short, 11/12 asset manager long/short, 14/15 leveraged funds long/short.
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

            if (count($cells) < 16) {
                continue;
            }

            $code = $cells[3];

            if (!in_array($code, $tracked, true)) {
                continue;
            }

            $reportDate = self::parseYymmdd($cells[1]);
            $numbers = array_map(
                [self::class, 'parseInt'],
                [$cells[7], $cells[8], $cells[9], $cells[11], $cells[12], $cells[14], $cells[15]],
            );

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

    /**
     * Yearly history files are published as a zip holding a single FinFutYY.txt.
     */
    public static function unzipFirstEntry(string $zipBytes): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw BalconyClientException::malformed('The zip extension is required to read CFTC history archives.');
        }

        $path = tempnam(sys_get_temp_dir(), 'cftc');

        if ($path === false) {
            throw BalconyClientException::malformed('Could not create a temp file for the CFTC archive.');
        }

        try {
            file_put_contents($path, $zipBytes);
            $zip = new ZipArchive();

            if ($zip->open($path) !== true) {
                throw BalconyClientException::malformed('CFTC archive is not a readable zip file.');
            }

            $contents = $zip->getFromIndex(0);
            $zip->close();

            if ($contents === false) {
                throw BalconyClientException::malformed('CFTC archive is empty.');
            }

            return $contents;
        } finally {
            @unlink($path);
        }
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
