<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REVISION_REQUIRED = 'revision_required';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'Sedang Berjalan',
            self::COMPLETED => 'Selesai',
            self::REVISION_REQUIRED => 'Perlu Revisi',
            self::REJECTED => 'Ditolak',
        };
    }
}
