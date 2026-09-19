<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use App\Models\Verification;

class VerificationPolicy
{
    public function create(User $user, Proposal $proposal): bool
    {
        $status = $proposal->status instanceof \BackedEnum
            ? $proposal->status->value
            : (string) $proposal->status;

        return $user->is_active
            && $this->hasVerificationRole($user)
            && in_array($status, ['submitted', 'verification'], true);
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

        $isAuthorizedUser = $this->hasAdministrativeRole($user)
            || ($this->hasVerificationRole($user) && ($verification->verifier_id === $user->id || $verification->verifier_id === null));

        return $user->is_active
            && $isAuthorizedUser
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
