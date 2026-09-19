<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case UNSIGNED = 'unsigned';
    case PENDING_SIGNATURE = 'pending_signature';
    case SIGNED = 'signed';
    case REJECTED = 'rejected';
    case REVOKED = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::UNSIGNED => 'Belum Ditandatangani',
            self::PENDING_SIGNATURE => 'Menunggu Tanda Tangan',
            self::SIGNED => 'Telah Ditandatangani',
            self::REJECTED => 'Ditolak',
            self::REVOKED => 'Dicabut',
        };
    }

    public function isSigned(): bool
    {
        return $this === self::SIGNED;
    }
}

