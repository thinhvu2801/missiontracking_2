<?php

namespace App\Enums;

class MissionTypeEnum extends BaseEnum
{    
    public const TIME_LIMITED  = 'time_limited';
    public const REGULAR = 'regular';

    public static function labelFor(string|int $value): string
    {
        return match ($value) {
            self::TIME_LIMITED  => 'Có thời hạn',            
            self::REGULAR => 'Thường xuyên',
            default => (string) $value,
        };
    }
}
