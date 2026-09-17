<?php

namespace App\Enums;

enum DisbursementTransactionStatus: string
{
    case RECORDED = 'recorded';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::RECORDED => 'Tercatat',
            self::CONFIRMED => 'Terkonfirmasi',
            self::FAILED => 'Gagal',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::CONFIRMED,
            self::CANCELLED,
        ], true);
    }
}
