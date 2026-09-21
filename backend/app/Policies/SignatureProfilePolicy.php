<?php

namespace App\Policies;

use App\Models\SignatureProfile;
use App\Models\User;

class SignatureProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR']);
    }

    public function view(User $user, SignatureProfile $profile): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR'])
            || $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    public function update(User $user, SignatureProfile $profile): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    public function uploadVisual(User $user, SignatureProfile $profile): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])
            || $profile->user_id === $user->id;
    }
}

