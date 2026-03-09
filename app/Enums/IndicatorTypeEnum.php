<?php

namespace App\Enums;

class IndicatorTypeEnum extends BaseEnum
{
    public const QUANTITATIVE = 'quantitative';
    public const QUALITATIVE  = 'qualitative';

    public static function labelFor(string|int $value): string
    {
        return match ($value) {
            self::QUANTITATIVE => 'Định lượng',
            self::QUALITATIVE  => 'Định tính',
            default => (string) $value,
        };
    }
}
