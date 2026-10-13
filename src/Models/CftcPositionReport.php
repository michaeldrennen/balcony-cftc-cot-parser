<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\CftcCotParser\Models;

use Illuminate\Database\Eloquent\Model;

final class CftcPositionReport extends Model
{
    protected $table = 'balcony_cftc_position_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'report_date' => 'date',
        'open_interest' => 'integer',
        'dealer_long' => 'integer',
        'dealer_short' => 'integer',
        'asset_mgr_long' => 'integer',
        'asset_mgr_short' => 'integer',
        'lev_funds_long' => 'integer',
        'lev_funds_short' => 'integer',
    ];
}
