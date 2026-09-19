<?php

namespace App\Enums;

enum SignatureType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL_PROVIDER = 'external_provider';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Tanda Tangan Digital Internal SIKOMANDO',
            self::EXTERNAL_PROVIDER => 'Penyedia Tanda Tangan Elektronik Eksternal',
        };
    }
}

