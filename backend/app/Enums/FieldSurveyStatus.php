<?php

namespace App\Enums;

enum FieldSurveyStatus: string
{
    case ASSIGNED = 'assigned';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case REVIEWED = 'reviewed';
    case REVISION_REQUIRED = 'revision_required';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED => 'Ditugaskan',
            self::SCHEDULED => 'Terjadwal',
            self::IN_PROGRESS => 'Sedang Berlangsung',
            self::SUBMITTED => 'Diajukan',
            self::REVIEWED => 'Ditinjau',
            self::REVISION_REQUIRED => 'Perlu Perbaikan',
            self::REJECTED => 'Ditolak',
            self::COMPLETED => 'Selesai',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::REJECTED,
        ], true);
    }

    public function canBeEditedBySurveyor(): bool
    {
        return in_array($this, [
            self::ASSIGNED,
            self::SCHEDULED,
            self::IN_PROGRESS,
            self::REVISION_REQUIRED,
        ], true);
    }
}
