<?php

namespace App\Policies;

use App\Models\DigitalSignature;
use App\Models\User;

class DigitalSignaturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR']);
    }

    public function view(User $user, DigitalSignature $signature): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR'])) {
            return true;
        }

        return $signature->signer_id === $user->id || $signature->requested_by === $user->id;
    }

    public function requestSignature(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR']);
    }

    public function sign(User $user, DigitalSignature $signature): bool
    {
        return $user->hasRole('SUPER_ADMIN') || $signature->signer_id === $user->id;
    }

    public function reject(User $user, DigitalSignature $signature): bool
    {
        return $user->hasRole('SUPER_ADMIN') || $signature->signer_id === $user->id;
    }

    public function revoke(User $user, DigitalSignature $signature): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']) || $signature->signer_id === $user->id;
    }
}

