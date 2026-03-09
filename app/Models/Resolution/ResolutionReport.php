<?php

namespace App\Models\Resolution;

use App\Enums\PeriodTypeEnum;
use App\Enums\UnitTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResolutionReport extends Model
{
    protected $table = 'resolution_reports';

    protected $fillable = [
        'resolution_id',
        'unit_type',
        'period_type',
    ];
    
    protected $casts = [
        'period_type' => PeriodTypeEnum::class,
        'unit_type'   => UnitTypeEnum::class
    ];

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class, 'resolution_id');
    }
}
