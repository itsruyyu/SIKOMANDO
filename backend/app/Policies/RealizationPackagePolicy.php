<?php

namespace App\Policies;

use App\Enums\RealizationPackageStatus;
use App\Models\Proposal;
use App\Models\RealizationPackage;
use App\Models\User;

class RealizationPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR', 'PEMOHON', 'SURVEYOR']);
    }

    public function view(User $user, RealizationPackage $package): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR', 'SURVEYOR'])) {
            return true;
        }

        return $package->proposal?->applicant_id === $user->id;
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

    public function update(User $user, RealizationPackage $package): bool
    {
        if ($package->status !== RealizationPackageStatus::DRAFT) {
            return false;
        }

        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $package->proposal?->applicant_id === $user->id;
    }

    public function submit(User $user, RealizationPackage $package): bool
    {
        if ($package->status !== RealizationPackageStatus::DRAFT) {
            return false;
        }

        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $package->proposal?->applicant_id === $user->id;
    }

    public function verify(User $user, RealizationPackage $package): bool
    {
        if ($package->created_by === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR']);
    }

    public function addItem(User $user, RealizationPackage $package): bool
    {
        return $this->update($user, $package);
    }

    public function inspect(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'SURVEYOR', 'VERIFIKATOR']);
    }
}

