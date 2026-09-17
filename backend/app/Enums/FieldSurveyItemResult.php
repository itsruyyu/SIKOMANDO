<?php

namespace App\Enums;

enum FieldSurveyItemResult: string
{
    case PENDING = 'pending';
    case PASS = 'pass';
    case FAIL = 'fail';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Diperiksa',
            self::PASS => 'Sesuai / Memenuhi',
            self::FAIL => 'Tidak Sesuai',
            self::NOT_APPLICABLE => 'Tidak Berlaku',
        };
    }
}
