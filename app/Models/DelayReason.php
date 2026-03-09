<?php
namespace App\Models;

use App\Models\Mission\MissionReport;
use Illuminate\Database\Eloquent\Model;

class DelayReason extends Model
{
    protected $table = 'delay_reasons';

    protected $fillable = [
        'reason_code',
        'reason_name',
    ];

    protected static function booted()
    {
        static::updating(function ($delayReason) {
            if ($delayReason->getOriginal('reason_code') == 'others') {
                return false;
            }
        });

        static::deleting(function ($delayReason) {
            if ($delayReason->reason_code === 'others') {
                return false;
            }
            $delayReason->missionReports()->detach();
        });
    }

    public function missionReports()
    {
        return $this->belongsToMany(
            MissionReport::class,
            'mission_report_delay_reasons',
            'delay_reason_id',
            'mission_report_id'
        )->withPivot('description')
         ->withTimestamps();
    }
}
