<?php

namespace App\Enums;

enum LpjStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case REVISION_REQUESTED = 'revision_requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FINALIZED = 'finalized';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf LPJ',
            self::SUBMITTED => 'LPJ Diajukan',
            self::UNDER_REVIEW => 'Sedang Ditinjau',
            self::REVISION_REQUESTED => 'Perlu Revisi',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::FINALIZED => 'Final',
            self::CLOSED => 'Selesai & Ditutup',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REVISION_REQUESTED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::FINALIZED, self::CLOSED, self::REJECTED], true);
    }

    public function canBeReviewed(): bool
    {
        return in_array($this, [self::SUBMITTED, self::UNDER_REVIEW], true);
    }

    public function canBeApproved(): bool
    {
        return in_array($this, [self::SUBMITTED, self::UNDER_REVIEW], true);
    }

    public function canBeFinalized(): bool
    {
        return $this === self::APPROVED;
    }

    public function canBeClosed(): bool
    {
        return in_array($this, [self::APPROVED, self::FINALIZED], true);
    }
}
