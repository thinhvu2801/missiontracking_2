<?php

namespace App\Models;

use App\Enums\PeriodTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReportPeriod extends Model
{
    protected $table = 'report_periods';

    public $timestamps = false;

    protected $fillable = [
        'period_type',
        'report_year',
        'period_number',
        'start_date',
        'end_date',
    ];
    protected $casts = [
        'period_type' => PeriodTypeEnum::class,
    ];
    public function canReport(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return Carbon::parse($this->end_date)
            ->addDay()
            ->endOfDay()
            ->isFuture();
    }
}
