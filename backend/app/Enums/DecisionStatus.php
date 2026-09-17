<?php

namespace App\Enums;

enum DecisionStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Keputusan',
            self::PUBLISHED => 'Diterbitkan',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::PUBLISHED,
            self::CANCELLED,
        ], true);
    }
}

