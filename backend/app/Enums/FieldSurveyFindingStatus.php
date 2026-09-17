<?php

namespace App\Enums;

enum FieldSurveyFindingStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Terbuka',
            self::IN_PROGRESS => 'Dalam Tindak Lanjut',
            self::RESOLVED => 'Selesai / Teratasi',
            self::CLOSED => 'Ditutup',
        };
    }
}
