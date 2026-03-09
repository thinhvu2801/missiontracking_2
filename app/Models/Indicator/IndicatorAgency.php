<?php

namespace App\Models\Indicator;

use App\Models\Agency\Agency;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IndicatorAgency extends Pivot
{
    protected $table = 'indicator_agency';

    protected $fillable = [
        'indicator_id',
        'agency_id',
    ];

    public $incrementing = true;

    protected static function booted(): void
    {
        static::deleting(function (IndicatorAgency $indicatorAgency): void {
            $indicatorAgency->reports()->delete();
        });
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function reports()
    {
        return $this->hasMany(IndicatorReport::class, 'indicator_agency_id');
    }
}
