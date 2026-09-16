<?php

namespace App\Enums;

enum VerificationItemResult: string
{
    case PENDING = 'pending';
    case PASS = 'pass';
    case FAIL = 'fail';
    case NEED_REVISION = 'need_revision';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Diperiksa',
            self::PASS => 'Lulus',
            self::FAIL => 'Tidak Lulus',
            self::NEED_REVISION => 'Perlu Revisi',
        };
    }
}
