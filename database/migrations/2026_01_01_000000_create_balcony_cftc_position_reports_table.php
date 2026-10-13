<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('balcony_cftc_position_reports', function (Blueprint $table): void {
            $table->id();
            $table->date('report_date');
            $table->string('contract_market_code', 32);
            $table->string('contract_name', 128);
            $table->unsignedBigInteger('open_interest');
            $table->unsignedBigInteger('dealer_long');
            $table->unsignedBigInteger('dealer_short');
            $table->unsignedBigInteger('asset_mgr_long');
            $table->unsignedBigInteger('asset_mgr_short');
            $table->unsignedBigInteger('lev_funds_long');
            $table->unsignedBigInteger('lev_funds_short');
            $table->timestamps();

            $table->unique(['report_date', 'contract_market_code'], 'balcony_cftc_report_contract_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balcony_cftc_position_reports');
    }
};
