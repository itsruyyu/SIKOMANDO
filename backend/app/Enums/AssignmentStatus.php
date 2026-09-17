<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case REVOKED = 'REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED => 'Ditugaskan',
            self::IN_PROGRESS => 'Sedang Dikerjakan',
            self::COMPLETED => 'Selesai',
            self::REVOKED => 'Dicabut',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::ASSIGNED, self::IN_PROGRESS], true);
    }
}
