<?php

namespace App\Models\Mission;

use App\Enums\MissionTypeEnum;
use App\Models\Agency\Agency;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    protected $table = 'missions';

    protected $fillable = [
        'mission_group_id',
        'mission_code',
        'mission_name',
        'mission_type',
        'expected_result',
        'deadline_date',
        'parent_mission_id',
        'created_by',
        'is_completed',
        'completed_at',
        'editable_until',
    ];

    protected $casts = [
        'mission_type' => MissionTypeEnum::class,
        'editable_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Mission $mission) {
            $mission->children()->update([
                'parent_mission_id' => null,
            ]);
            $mission->missionAgencies()->each(function ($ma) {
                $ma->delete();
            });
            $mission->agencies()->detach();
            $mission->reportPeriods()->delete();
            $mission->dashboardStats()->delete();
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MissionGroup::class, 'mission_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_mission_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_mission_id');
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Agency::class,
            'mission_agency',
            'mission_id',
            'agency_id'
        )->using(MissionAgency::class)
        ->withPivot('id', 'children_agency_id', 'is_completed', 'completed_at')
        ->withTimestamps();
    }
    
    public function missionAgencies()
    {
        return $this->hasMany(MissionAgency::class, 'mission_id');
    }

    public function childrenAgencies()
    {
        return $this->hasManyThrough(
            Agency::class,
            MissionAgency::class,
            'mission_id',          // FK trên mission_agency
            'id',                  // PK trên agencies
            'id',                  // PK trên missions
            'children_agency_id'   // FK trên mission_agency
        );
    }

    public function reportPeriods(): HasMany
    {
        return $this->hasMany(MissionReportPeriod::class);
    }
    
    public function dashboardStats(): HasMany
    {
        return $this->hasMany(MissionDashboardStat::class);
    }


    /* ================== HELPERS ================== */

    public function getChildren()
    {
        return $this->children()->with('getChildren');
    }

    public function getEffectivePeriodTypes(): array
    {
        if ($this->reportPeriods->isNotEmpty()) {
            return $this->reportPeriods
                ->pluck('period_type')
                ->unique()
                ->values()
                ->toArray();
        }

        // fallback: kỳ báo cáo của văn bản
        return $this->group
            ->resolution
            ->reports
            ->where('unit_type', 'mission')
            ->pluck('period_type')
            ->unique()
            ->values()
            ->toArray();
    }
    public function getAgenciesDisplay()
    {
        $agencies = $this->agencies()->with('group')->whereNull('parent_agency_id')->get();

        if ($agencies->isEmpty()) {
            return '';
        }

        $grouped = $agencies->groupBy('agency_group_id');

        $results = collect();

        foreach ($grouped as $groupId => $groupAgencies) {
            $totalInGroup = Agency::where('agency_group_id', $groupId)->whereNull('parent_agency_id')->count();
            $assignedCount = $groupAgencies->count();
            if ($assignedCount == $totalInGroup) {
                $results->push(
                    optional($groupAgencies->first()->group)->group_name
                );
            } else {
                foreach ($groupAgencies as $agency) {
                    $results->push($agency->agency_name);
                }
            }
        }

        return $results->implode('; ');
    }
    
    public function canEdit(): bool
    {
        return $this->editable_until
            && now()->lessThan($this->editable_until);
    }

}
