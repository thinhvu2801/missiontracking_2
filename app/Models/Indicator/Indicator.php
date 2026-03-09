<?php

namespace App\Models\Indicator;

use App\Enums\IndicatorTypeEnum;
use App\Models\Agency\Agency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    protected $table = 'indicators';

    protected $fillable = [
        'indicator_group_id',
        'indicator_code',
        'indicator_name',
        'unit_of_measure',
        'indicator_type',
        'expected_result',
        'target_min',
        'target_max',
        'is_target_min_equal',
        'is_target_max_equal',
        'parent_indicator_id',
    ];

    protected $casts = [
        'indicator_type' => IndicatorTypeEnum::class,
        'is_target_min_equal' => 'boolean',
        'is_target_max_equal' => 'boolean',
        'target_min' => 'decimal:2',
        'target_max' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Indicator $indicator): void {
            $indicator->children()->update([
                'parent_indicator_id' => null,
            ]);
            $indicator->agencies->each(function ($agency) use ($indicator) {
                $pivot = $agency->pivot;
                $pivot->reports()->delete();
            });

            $indicator->agencies()->detach();
            $indicator->reportPeriods()->delete();
        });
    }


    public function group(): BelongsTo
    {
        return $this->belongsTo(IndicatorGroup::class, 'indicator_group_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_indicator_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_indicator_id');
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class,
            'indicator_agency',
            'indicator_id',
            'agency_id'
        )->using(IndicatorAgency::class)
        ->withPivot('id')
        ->withTimestamps();
    }

    
    public function reportPeriods()
    {
        return $this->hasMany(IndicatorReportPeriod::class);
    }

    public function getAgenciesDisplay()
    {
        $agencies = $this->agencies()->with('group')->get();

        if ($agencies->isEmpty()) {
            return '';
        }

        $grouped = $agencies->groupBy('agency_group_id');

        $results = collect();

        foreach ($grouped as $groupId => $groupAgencies) {
            $totalInGroup = Agency::where('agency_group_id', $groupId)->count();
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

    public function getExpectedResult(): string
    {
        if ($this->expected_result != null) {
            return $this->expected_result;
        }

        $min = $this->target_min;
        $max = $this->target_max;

        if ($min == null && $max == null) {
            return '';
        }

        if ($min == null) {
            return ($this->is_target_max_equal ? '≤ ' : '< ')
                . format_number($max);
        }

        if ($max == null) {
            return ($this->is_target_min_equal ? '≥ ' : '> ')
                . format_number($min);
        }

        if ($min != null && $min == $max){
            return format_number($min);            
        }

        return format_number($min) . ' - ' . format_number($max);
    }

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

        // fallback: kỳ của văn bản
        return $this->group
            ->resolution
            ->reports
            ->where('unit_type', 'indicator')
            ->pluck('period_type')
            ->unique()
            ->values()
            ->toArray();
    }
}
