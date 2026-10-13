<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser\Tests;

use DateTimeImmutable;
use MichaelDrennen\Balcony\CftcCotParser\DTO\CotPositionDTO;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testDtoMapsToModelAttributes(): void
    {
        $dto = new CotPositionDTO(
            new DateTimeImmutable('2026-10-06'),
            '043602',
            '10-YEAR U.S. TREASURY NOTES - CHICAGO BOARD OF TRADE',
            3845210, 412000, 325000, 1250000, 540000, 890000, 1450000,
        );

        $attrs = $dto->asModelAttributes();

        $this->assertSame('2026-10-06', $attrs['report_date']);
        $this->assertSame('043602', $attrs['contract_market_code']);
        $this->assertSame(1250000, $attrs['asset_mgr_long']);
        $this->assertSame(1450000, $attrs['lev_funds_short']);
    }

    public function testEloquentModelAcceptsDtoAttributes(): void
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            $this->markTestSkipped('illuminate/database is not installed.');
        }

        // Eloquent's guarded-attribute check reads the column listing, so the model needs a connection.
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $dto = new CotPositionDTO(new DateTimeImmutable('2026-10-06'), '042601', 'UST 2Y', 1, 2, 3, 4, 5, 6, 7);
        $model = new \MichaelDrennen\Balcony\CftcCotParser\Models\CftcPositionReport($dto->asModelAttributes());

        $this->assertSame('balcony_cftc_position_reports', $model->getTable());
        $this->assertSame('042601', $model->contract_market_code);
    }
}
