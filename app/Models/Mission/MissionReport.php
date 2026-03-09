<?php

namespace App\Models\Mission;

use App\Models\DelayReason;
use App\Models\ReportPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionReport extends Model
{
    protected $table = 'mission_reports';

    protected $fillable = [
        'mission_agency_id',
        'report_period_id',
        'status',
        'execution_result',
        'progress_percent',
        'recommendation',
    ];

    protected $casts = [
        'status' => 'boolean',
        'progress_percent' => 'float',
    ];

    protected static function booted()
    {
        static::deleting(function ($missionReport) {
            $missionReport->delayReasons()->detach();
        });
    }

    public function missionAgency(): BelongsTo
    {
        return $this->belongsTo(MissionAgency::class);
    }

    public function reportPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportPeriod::class);
    }
    public function delayReasons()
    {
        return $this->belongsToMany(DelayReason::class,
            'mission_report_delay_reasons',
            'mission_report_id',
            'delay_reason_id'
        )->withPivot('description')
         ->withTimestamps();
    }
}
