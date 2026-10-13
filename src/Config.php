<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser;

use InvalidArgumentException;

final class Config
{
    public const CURRENT_WEEK_URL = 'https://www.cftc.gov/dea/newcot/FinFutWk.txt';

    public const HISTORY_URL_TEMPLATE = 'https://www.cftc.gov/files/dea/history/fut_fin_txt_{YYYY}.zip';

    /** @var string[] */
    public const DEFAULT_TRACKED_CODES = ['042601', '044601', '043602', '020601', '13874+'];

    /**
     * @param int $year 0 fetches the latest weekly report; a year fetches that year's history archive.
     * @param string[] $trackedCodes
     */
    public function __construct(
        public readonly int $year = 0,
        public readonly array $trackedCodes = self::DEFAULT_TRACKED_CODES,
        public readonly string $currentWeekUrl = self::CURRENT_WEEK_URL,
        public readonly string $historyUrlTemplate = self::HISTORY_URL_TEMPLATE,
        public readonly float $timeoutSeconds = 30.0,
    ) {
        if ($year !== 0 && ($year < 2000 || $year > 2099)) {
            throw new InvalidArgumentException('CFTC year must be between 2000 and 2099 (or 0 for the latest week).');
        }

        if ($trackedCodes === []) {
            throw new InvalidArgumentException('At least one CFTC contract code must be tracked.');
        }
    }

    public function url(): string
    {
        if ($this->year === 0) {
            return $this->currentWeekUrl;
        }

        return str_replace('{YYYY}', (string) $this->year, $this->historyUrlTemplate);
    }
}
