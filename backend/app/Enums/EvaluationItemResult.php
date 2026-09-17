<?php

namespace App\Enums;

enum EvaluationItemResult: string
{
    case PENDING = 'PENDING';
    case PASS = 'PASS';
    case FAIL = 'FAIL';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Dinilai',
            self::PASS => 'Memenuhi',
            self::FAIL => 'Tidak Memenuhi',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::PASS,
            self::FAIL,
        ], true);
    }
}
