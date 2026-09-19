<?php

namespace App\Enums;

enum HandoverStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case SIGNED = 'signed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf Berita Acara',
            self::SUBMITTED => 'Diajukan',
            self::SIGNED => 'Ditandatangani',
            self::COMPLETED => 'Selesai & Diserahterimakan',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}

