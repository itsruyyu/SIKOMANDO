<?php

namespace App\Policies;

use App\Enums\ReceiptStatus;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR', 'PEMOHON']);
    }

    public function view(User $user, Receipt $receipt): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR'])) {
            return true;
        }

        return $receipt->proposal?->applicant_id === $user->id;
    }

    public function create(User $user, ?Proposal $proposal = null): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        if (! $user->hasRole('PEMOHON') || ! $proposal) {
            return false;
        }

        return $proposal->applicant_id === $user->id
            && in_array($proposal->status?->value ?? (string) $proposal->status, ['disbursed', 'implementation'], true);
    }

    public function update(User $user, Receipt $receipt): bool
    {
        if ($receipt->status !== ReceiptStatus::DRAFT) {
            return false;
        }

        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $receipt->proposal?->applicant_id === $user->id;
    }

    /**
     * Maker-Checker rule: Pembuat kuitansi dilarang memverifikasi kuitansinya sendiri.
     */
    public function verify(User $user, Receipt $receipt): bool
    {
        if ($receipt->created_by === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR']);
    }

    public function cancel(User $user, Receipt $receipt): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $receipt->proposal?->applicant_id === $user->id
            && $receipt->status === ReceiptStatus::DRAFT;
    }
}

