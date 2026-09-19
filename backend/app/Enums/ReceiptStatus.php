<?php

namespace App\Enums;

enum ReceiptStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case VERIFIED = 'verified';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf Kuitansi',
            self::ISSUED => 'Diterbitkan',
            self::VERIFIED => 'Terverifikasi',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}

