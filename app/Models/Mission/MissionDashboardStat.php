<?php

namespace App\Models\Mission;

use App\Models\ReportPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionDashboardStat extends Model
{
    protected $table = 'mission_dashboard_stats';

    protected $fillable = [
        'mission_id',
        'report_period_id',
        'total_agencies',
        'reported_count',
        'completed_count',
        'on_time_count',
    ];

    protected $casts = [
        'total_agencies'  => 'integer',
        'reported_count'  => 'integer',
        'completed_count' => 'integer',
        'on_time_count'   => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($stat) {

            $exists = self::where('mission_id', $stat->mission_id)
                ->where('report_period_id', $stat->report_period_id)
                ->exists();

            if ($exists) {
                return false;
            }
        });
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function reportPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportPeriod::class, 'report_period_id');
    }
}
