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

final class ClientTest extends TestCase
{
    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.txt');
    }

    public function testParsesTrackedContractsOnly(): void
    {
        $dtos = (new Client(new Config(year: 2026)))->parse($this->fixture());

        $this->assertCount(2, $dtos);
        $this->assertSame('043602', $dtos[0]->contractCode);
        $this->assertSame('2026-10-06', $dtos[0]->reportDate->format('Y-m-d'));
        $this->assertSame(3845210, $dtos[0]->openInterest);
        $this->assertSame(1450000, $dtos[0]->leveragedFundsShort);
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

    public function testUrlUsesTwoDigitYear(): void
    {
        $this->assertStringEndsWith('FinFut26.txt', (new Config(year: 2026))->url());
    }

    public function testInvalidYearRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config(year: 1800);
    }
}
