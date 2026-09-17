<?php

namespace App\Enums;

enum AssignmentType: string
{
    case VERIFICATION = 'VERIFICATION';
    case EVALUATION = 'EVALUATION';
    case FIELD_SURVEY = 'FIELD_SURVEY';
    case AUDIT = 'AUDIT';

    public function label(): string
    {
        return match ($this) {
            self::VERIFICATION => 'Verifikasi',
            self::EVALUATION => 'Evaluasi',
            self::FIELD_SURVEY => 'Survei Lapangan',
            self::AUDIT => 'Audit',
        };
    }

    /**
     * Return the role code that corresponds to this assignment type.
     */
    public function requiredRole(): string
    {
        return match ($this) {
            self::VERIFICATION => 'VERIFIKATOR',
            self::EVALUATION => 'EVALUATOR',
            self::FIELD_SURVEY => 'SURVEYOR',
            self::AUDIT => 'AUDITOR',
        };
    }
}
