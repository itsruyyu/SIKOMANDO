<?php

namespace App\Enums;

enum RecommendationResult: string
{
    case PENDING = 'pending';
    case RECOMMENDED = 'recommended';
    case NOT_RECOMMENDED = 'not_recommended';
    case CONSIDERATION = 'consideration';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Keputusan',
            self::RECOMMENDED => 'Direkomendasikan',
            self::NOT_RECOMMENDED => 'Tidak Direkomendasikan',
            self::CONSIDERATION => 'Perlu Pertimbangan Khusus',
        };
    }
}
