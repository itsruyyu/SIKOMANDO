<?php

namespace App\Enums;

enum QrStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case SUPERSEDED = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktif',
            self::REVOKED => 'Dicabut',
            self::EXPIRED => 'Kedaluwarsa',
            self::SUPERSEDED => 'Digantikan',
        };
    }

    public function isValid(): bool
    {
        return $this === self::ACTIVE;
    }
}

