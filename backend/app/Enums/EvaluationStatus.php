<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case DRAFT = 'DRAFT';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'Sedang Berlangsung',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::IN_PROGRESS,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ], true);
    }
}
