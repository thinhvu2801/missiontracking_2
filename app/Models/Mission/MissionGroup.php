<?php

namespace App\Models\Mission;

use App\Models\Resolution\Resolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionGroup extends Model
{
    protected $table = 'mission_groups';

    protected $fillable = [
        'resolution_id',
        'group_code',
        'group_name',
    ];

    protected static function booted(): void
    {
        static::deleting(function (MissionGroup $group): void {
            $group->missions->each(
                fn (Mission $mission) => $mission->delete()
            );
        });
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class, 'resolution_id');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'mission_group_id');
    }
}
