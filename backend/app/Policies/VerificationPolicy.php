<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use App\Models\Verification;

class VerificationPolicy
{
    public function create(User $user, Proposal $proposal): bool
    {
        return $user->is_active
            && $this->hasVerificationRole($user)
            && $proposal->status?->value === 'verification';
    }

    public function view(User $user, Verification $verification): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($this->hasAdministrativeRole($user)) {
            return true;
        }

        if (
            $this->hasVerificationRole($user)
            && $verification->verifier_id === $user->id
        ) {
            return true;
        }

        return $verification->proposal()
            ->where('applicant_id', $user->id)
            ->exists();
    }

    public function update(User $user, Verification $verification): bool
    {
        $statusValue = $verification->status instanceof \BackedEnum
            ? $verification->status->value
            : (string) $verification->status;

        return $user->is_active
            && $this->hasVerificationRole($user)
            && $verification->verifier_id === $user->id
            && $statusValue === 'in_progress';
    }

    public function complete(User $user, Verification $verification): bool
    {
        return $this->update($user, $verification);
    }

    private function hasVerificationRole(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN_SIKOMANDO')
            || $user->hasRole('VERIFIKATOR');
    }

    private function hasAdministrativeRole(User $user): bool
    {
        return $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN_SIKOMANDO');
    }
}
