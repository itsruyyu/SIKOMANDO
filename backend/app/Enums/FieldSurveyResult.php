<?php

namespace App\Enums;

enum FieldSurveyResult: string
{
    case PENDING = 'pending';
    case RECOMMENDED = 'recommended';
    case REVISION_REQUIRED = 'revision_required';
    case NOT_RECOMMENDED = 'not_recommended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Penilaian',
            self::RECOMMENDED => 'Direkomendasikan',
            self::REVISION_REQUIRED => 'Perlu Perbaikan',
            self::NOT_RECOMMENDED => 'Tidak Direkomendasikan',
        };
    }
}
