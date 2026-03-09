<?php

namespace App\Models\Mission;

use App\Models\Agency\Agency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MissionAgency extends Pivot
{
    protected $table = 'mission_agency';

    protected $fillable = [
        'mission_id',
        'agency_id',
        'children_agency_id',
        'is_completed',
        'completed_at',        
    ];

    public $incrementing = true;

    protected static function booted(): void
    {
        static::deleting(function (MissionAgency $missionAgency): void {
            $missionAgency->reports()->delete();
        });
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function reports()
    {
        return $this->hasMany(MissionReport::class, 'mission_agency_id');
    }

    public function childrenAgency()
    {
        return $this->belongsTo(Agency::class, 'children_agency_id');
    }

    public function canEdit(): bool
    {
        if (! $this->is_completed || ! $this->completed_at) {
            return true;
        }

        return Carbon::parse($this->completed_at)->addDay()->isFuture();
    }
}
