<?php

namespace App\Enums;

class PeriodTypeEnum extends BaseEnum
{
    public const WEEK      = 'week';
    public const MONTH     = 'month';
    public const QUARTER   = 'quarter';
    public const HALF_YEAR = 'half_year';
    public const YEAR      = 'year';

    public static function labelFor(string|int $value): string
    {
        return match ($value) {
            self::WEEK      => 'Tuần',
            self::MONTH     => 'Tháng',
            self::QUARTER   => 'Quý',
            self::HALF_YEAR => '6 tháng',
            self::YEAR      => 'Năm',
            default => (string) $value,
        };
    }
}
