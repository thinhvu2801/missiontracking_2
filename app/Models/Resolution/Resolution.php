<?php

namespace App\Models\Resolution;

use App\Models\Indicator\IndicatorGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resolution extends Model
{
    protected $table = 'resolutions';

    protected $fillable = [
        'resolution_code',
        'resolution_name',
        'issued_date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Resolution $resolution): void {
            $resolution->indicatorGroups->each(
                fn (IndicatorGroup $group) => $group->delete()
            );
            $resolution->reports()->delete();
        });
    }

    public function indicatorGroups(): HasMany
    {
        return $this->hasMany(IndicatorGroup::class, 'resolution_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ResolutionReport::class, 'resolution_id');
    }
}
