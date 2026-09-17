<?php

namespace App\Enums;

enum LpjItemStatus: string
{
    case REPORTED = 'reported';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::REPORTED => 'Dilaporkan',
            self::VERIFIED => 'Diverifikasi Sesuai',
            self::REJECTED => 'Ditolak / Tidak Sesuai',
        };
    }
}
