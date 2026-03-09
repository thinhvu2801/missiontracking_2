<?php

namespace App\Models\Agency;

use App\Models\Indicator\IndicatorAgency;
use App\Models\Mission\MissionAgency;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $fillable = [
        'agency_name',
        'parent_agency_id',
        'agency_group_id',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Agency $agency): void {
            if ($agency->parent_agency_id == $agency->id) {
                $agency->parent_agency_id = null;
            }
        });
        static::deleting(function (Agency $agency): void {
            $agency->indicators()->delete();

            MissionAgency::where('children_agency_id', $agency->id)
                ->update(['children_agency_id' => null]);

            MissionAgency::where('agency_id', $agency->id)
                ->each(fn (MissionAgency $ma) => $ma->delete());

            $agency->children->each(
                fn (Agency $child) => $child->delete()
            );
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'parent_agency_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Agency::class, 'parent_agency_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AgencyGroup::class, 'agency_group_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(IndicatorAgency::class, 'agency_id');
    }

    public function managedMissions(): HasMany
    {
        return $this->hasMany(MissionAgency::class, 'agency_id');
    }

    public function assignedMissions(): HasMany
    {
        return $this->hasMany(MissionAgency::class, 'children_agency_id');
    }

}
