<?php

namespace App\Models\Indicator;

use App\Models\ReportPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorReport extends Model
{
    protected $table = 'indicator_reports';

    protected $fillable = [
        'indicator_agency_id',
        'report_period_id',
        'quantitive_result',
        'qualitive_result',
        'note',
    ];

    protected $casts = [
        'quantitive_result' => 'decimal:2',
        'qualitive_result'  => 'boolean',
    ];

    public function indicatorAgency(): BelongsTo
    {
        return $this->belongsTo(IndicatorAgency::class);
    }

    public function reportPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportPeriod::class);
    }
}
