<?php

namespace App\Enums;

enum ApprovalActionType: string
{
    case SUBMIT = 'submit';
    case REVIEW = 'review';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case CANCEL = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::SUBMIT => 'Pengajuan Persetujuan',
            self::REVIEW => 'Peninjauan',
            self::APPROVE => 'Persetujuan',
            self::REJECT => 'Penolakan',
            self::CANCEL => 'Pembatalan',
        };
    }
}

