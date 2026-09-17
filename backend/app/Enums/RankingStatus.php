<?php

namespace App\Enums;

enum RankingStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case REVIEWED = 'reviewed';
    case FINALIZED = 'finalized';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::GENERATED => 'Dihasilkan',
            self::REVIEWED => 'Ditinjau',
            self::FINALIZED => 'Final',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::FINALIZED,
            self::CANCELLED,
        ], true);
    }

    public function canBeRegenerated(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::GENERATED,
            self::REVIEWED,
        ], true);
    }
}
