<?php

namespace App\Enums;

enum EventCostStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Passed = 'passed';
    case Unknown = 'unknown';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
