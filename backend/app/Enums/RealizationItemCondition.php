<?php

namespace App\Enums;

enum RealizationItemCondition: string
{
    case GOOD = 'good';
    case DAMAGED = 'damaged';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Baik / Berfungsi Normal',
            self::DAMAGED => 'Rusak / Perlu Perbaikan',
            self::LOST => 'Hilang',
        };
    }
}

