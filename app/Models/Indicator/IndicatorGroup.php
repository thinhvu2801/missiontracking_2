<?php

namespace App\Models\Indicator;

use App\Models\Resolution\Resolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicatorGroup extends Model
{
    protected $table = 'indicator_groups';

    protected $fillable = [
        'resolution_id',
        'group_code',
        'group_name',
    ];

    protected static function booted(): void
    {
        static::deleting(function (IndicatorGroup $group): void {
            $group->indicators->each(
                fn (Indicator $indicator) => $indicator->delete()
            );
        });
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class, 'resolution_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class, 'indicator_group_id');
    }
}
