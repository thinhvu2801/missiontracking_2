<?php

namespace App\Models\Indicator;

use Illuminate\Database\Eloquent\Model;

class IndicatorReportPeriod extends Model
{
    protected $table = 'indicator_report_periods';

    protected $fillable = [
        'indicator_id',
        'period_type',
    ];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }
}
