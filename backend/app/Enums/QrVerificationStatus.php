<?php

namespace App\Enums;

enum QrVerificationStatus: string
{
    case VALID = 'VALID';
    case REVOKED = 'REVOKED';
    case EXPIRED = 'EXPIRED';
    case SUPERSEDED = 'SUPERSEDED';
    case INVALID = 'INVALID';
    case NOT_FOUND = 'NOT_FOUND';

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid dan Terverifikasi',
            self::REVOKED => 'QR Telah Dicabut',
            self::EXPIRED => 'QR Telah Kedaluwarsa',
            self::SUPERSEDED => 'QR Telah Digantikan Versi Baru',
            self::INVALID => 'QR Tidak Valid atau Terjadi Kerusakan Data',
            self::NOT_FOUND => 'QR Tidak Ditemukan dalam Sistem',
        };
    }
}

