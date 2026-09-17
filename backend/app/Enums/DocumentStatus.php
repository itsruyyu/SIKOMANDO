<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case UPLOADED = 'uploaded';
    case ACTIVE = 'active';
    case REPLACED = 'replaced';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::UPLOADED => 'Diunggah',
            self::ACTIVE => 'Aktif',
            self::REPLACED => 'Digantikan',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
            self::ARCHIVED => 'Diarsipkan',
            self::DELETED => 'Dihapus',
        };
    }
}
