<?php

namespace App\Enums;

enum DisbursementPlanStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Rencana',
            self::SUBMITTED => 'Diajukan',
            self::APPROVED => 'Disetujui',
            self::COMPLETED => 'Selesai Dicairkan',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ], true);
    }
}
