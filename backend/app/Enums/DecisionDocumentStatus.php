<?php

namespace App\Enums;

enum DecisionDocumentStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SUPERSEDED = 'superseded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Dokumen',
            self::ACTIVE => 'Aktif / Berlaku',
            self::SUPERSEDED => 'Digantikan',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
