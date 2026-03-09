<?php

namespace App\Models\Agency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyGroup extends Model
{
    protected $fillable = [
        'group_name',
        'description',
    ];

    protected static function booted(): void
    {
        static::deleted(function (AgencyGroup $agencyGroup): void {
            $agencyGroup->agencies()->each(fn (Agency $agency) => $agency->delete());
        });
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

}
