<?php

namespace App\Enums;

enum DisbursementStatus: string
{
    case PLANNED = 'planned';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case APPROVED = 'approved';
    case PROCESSED = 'processed';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Direncanakan',
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::APPROVED => 'Disetujui',
            self::PROCESSED => 'Diproses',
            self::PAID => 'Dicairkan / Lunas',
            self::FAILED => 'Gagal',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::PAID,
            self::CANCELLED,
        ], true);
    }

    public function canBeVerified(): bool
    {
        return in_array($this, [
            self::PLANNED,
            self::PENDING,
        ], true);
    }

    public function canBeApproved(): bool
    {
        return in_array($this, [
            self::VERIFIED,
            self::PLANNED,
            self::PENDING,
        ], true);
    }

    public function canBePaid(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::PROCESSED,
        ], true);
    }
}
