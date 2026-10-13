# balcony-cftc-cot-parser

PHP 8.1+ parser for CFTC Traders in Financial Futures (TFF) reports, part of The Balcony macro flow system. It downloads the yearly `FinFutYY.txt` file and keeps only the tracked contracts.

## Usage (framework-agnostic)

```php
use MichaelDrennen\Balcony\CftcCotParser\Client;
use MichaelDrennen\Balcony\CftcCotParser\Config;

$client = new Client(new Config(year: 2026));

foreach ($client->fetchReports() as $dto) {
    // $dto->reportDate, $dto->contractCode, $dto->leveragedFundsLong, ...
}
```

Default tracked codes: `042601` (2Y), `044601` (5Y), `043602` (10Y), `020601` (30Y), `13874+` (E-mini S&P 500). Override with `Config::$trackedCodes`.

## Laravel persistence

```php
use MichaelDrennen\Balcony\CftcCotParser\Models\CftcPositionReport;

foreach ($client->fetchReports() as $dto) {
    CftcPositionReport::updateOrCreate(
        ['report_date' => $dto->reportDate->format('Y-m-d'), 'contract_market_code' => $dto->contractCode],
        $dto->asModelAttributes(),
    );
}
```

## Errors

Failures throw `MichaelDrennen\Balcony\CftcCotParser\Exceptions\BalconyClientException`. HTTP 429 sets `isRateLimited()` and exposes `retryAfterSeconds`.

## Tests

```
composer install
vendor/bin/phpunit
```
