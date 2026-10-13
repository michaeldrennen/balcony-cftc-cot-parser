<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use MichaelDrennen\Balcony\CftcCotParser\Client;
use MichaelDrennen\Balcony\CftcCotParser\Config;
use MichaelDrennen\Balcony\CftcCotParser\Exceptions\BalconyClientException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ClientTest extends TestCase
{
    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.txt');
    }

    public function testParsesTrackedContractsOnly(): void
    {
        $dtos = (new Client(new Config()))->parse($this->fixture());

        $this->assertCount(2, $dtos);
        $this->assertSame('043602', $dtos[0]->contractCode);
        $this->assertSame('UST 10Y NOTE - CHICAGO BOARD OF TRADE', $dtos[0]->contractName);
        $this->assertSame('2026-10-06', $dtos[0]->reportDate->format('Y-m-d'));
        $this->assertSame(5742990, $dtos[0]->openInterest);
        $this->assertSame(98637, $dtos[0]->dealerLong);
        $this->assertSame(740670, $dtos[0]->dealerShort);
        $this->assertSame(3602149, $dtos[0]->assetManagerLong);
        $this->assertSame(774010, $dtos[0]->assetManagerShort);
        $this->assertSame(510611, $dtos[0]->leveragedFundsLong);
        $this->assertSame(2650396, $dtos[0]->leveragedFundsShort);
        $this->assertSame('042601', $dtos[1]->contractCode);
    }

    public function testFetchUnzipsYearlyArchive(): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'cftctest');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('FinFutYY.txt', $this->fixture());
        $zip->close();
        $zipBytes = (string) file_get_contents($path);
        unlink($path);

        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $zipBytes)])),
        ]);

        $this->assertCount(2, (new Client(new Config(year: 2026), $http))->fetchReports());
    }

    public function testFetchUsesMockedHttp(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(200, [], $this->fixture())])),
        ]);

        $this->assertCount(2, (new Client(new Config(year: 2026), $http))->fetchReports());
    }

    public function testRateLimitThrows(): void
    {
        $http = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([new Response(429, ['Retry-After' => '60'], '')])),
            'http_errors' => false,
        ]);

        try {
            (new Client(new Config(year: 2026), $http))->fetchReports();
            $this->fail('Expected BalconyClientException.');
        } catch (BalconyClientException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(60, $e->retryAfterSeconds);
        }
    }

    public function testDefaultUrlIsLatestWeek(): void
    {
        $this->assertStringEndsWith('/dea/newcot/FinFutWk.txt', (new Config())->url());
    }

    public function testYearUrlIsHistoryArchive(): void
    {
        $this->assertStringEndsWith('/history/fut_fin_txt_2026.zip', (new Config(year: 2026))->url());
    }

    public function testInvalidYearRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config(year: 1800);
    }
}
