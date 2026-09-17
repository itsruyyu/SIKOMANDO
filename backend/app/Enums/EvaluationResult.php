<?php

namespace App\Enums;

enum EvaluationResult: string
{
    case PENDING = 'PENDING';
    case RECOMMENDED = 'RECOMMENDED';
    case NOT_RECOMMENDED = 'NOT_RECOMMENDED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Diputuskan',
            self::RECOMMENDED => 'Direkomendasikan',
            self::NOT_RECOMMENDED => 'Tidak Direkomendasikan',
        };
    }
}
