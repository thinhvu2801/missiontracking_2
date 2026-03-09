<?php

namespace App\Models\Mission;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionReportPeriod extends Model
{
    protected $table = 'mission_report_periods';

    protected $fillable = [
        'mission_id',
        'period_type',
    ];
    
    protected static function booted()
    {
        static::creating(function ($period) {

            $exists = self::where('mission_id', $period->mission_id)
                ->where('period_type', $period->period_type)
                ->exists();

            if ($exists) {
                return false; // hủy create, không throw lỗi
            }
        });
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }
}
