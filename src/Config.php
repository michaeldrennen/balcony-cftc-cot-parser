<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser;

use InvalidArgumentException;

final class Config
{
    public const DEFAULT_URL_TEMPLATE = 'https://www.cftc.gov/dea/newcot/FinFut{YY}.txt';

    /** @var string[] */
    public const DEFAULT_TRACKED_CODES = ['042601', '044601', '043602', '020601', '13874+'];

    /**
     * @param string[] $trackedCodes
     */
    public function __construct(
        public readonly int $year = 0,
        public readonly array $trackedCodes = self::DEFAULT_TRACKED_CODES,
        public readonly string $urlTemplate = self::DEFAULT_URL_TEMPLATE,
        public readonly float $timeoutSeconds = 30.0,
    ) {
        if ($year !== 0 && ($year < 2000 || $year > 2099)) {
            throw new InvalidArgumentException('CFTC year must be between 2000 and 2099 (or 0 for the current year).');
        }

        if ($trackedCodes === []) {
            throw new InvalidArgumentException('At least one CFTC contract code must be tracked.');
        }
    }

    public function url(): string
    {
        $year = $this->year === 0 ? (int) date('Y') : $this->year;

        return str_replace('{YY}', sprintf('%02d', $year % 100), $this->urlTemplate);
    }
}
