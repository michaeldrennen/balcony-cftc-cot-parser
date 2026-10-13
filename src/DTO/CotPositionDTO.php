<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser\DTO;

use DateTimeImmutable;

final class CotPositionDTO implements MetricObservation
{
    public function __construct(
        public readonly DateTimeImmutable $reportDate,
        public readonly string $contractCode,
        public readonly string $contractName,
        public readonly int $openInterest,
        public readonly int $dealerLong,
        public readonly int $dealerShort,
        public readonly int $assetManagerLong,
        public readonly int $assetManagerShort,
        public readonly int $leveragedFundsLong,
        public readonly int $leveragedFundsShort,
    ) {
    }

    public function asModelAttributes(): array
    {
        return [
            'report_date' => $this->reportDate->format('Y-m-d'),
            'contract_market_code' => $this->contractCode,
            'contract_name' => $this->contractName,
            'open_interest' => $this->openInterest,
            'dealer_long' => $this->dealerLong,
            'dealer_short' => $this->dealerShort,
            'asset_mgr_long' => $this->assetManagerLong,
            'asset_mgr_short' => $this->assetManagerShort,
            'lev_funds_long' => $this->leveragedFundsLong,
            'lev_funds_short' => $this->leveragedFundsShort,
        ];
    }
}
