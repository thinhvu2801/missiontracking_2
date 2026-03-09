<?php

namespace App\Enums;

class UnitTypeEnum extends BaseEnum
{
    public const INDICATOR = 'indicator';
    public const MISSION  = 'mission';

    public static function labelFor(string|int $value): string
    {
        return match ($value) {
            self::INDICATOR => 'Chỉ tiêu',
            self::MISSION  => 'Nhiệm vụ',
            default => (string) $value,
        };
    }
}
