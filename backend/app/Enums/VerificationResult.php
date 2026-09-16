<?php

namespace App\Enums;

enum VerificationResult: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case NEED_REVISION = 'need_revision';

    public function label(): string
    {
        return match ($this) {
            self::PASS => 'Lulus',
            self::FAIL => 'Tidak Lulus',
            self::NEED_REVISION => 'Perlu Revisi',
        };
    }
}
