<?php

namespace App\Policies;

use App\Enums\HandoverStatus;
use App\Models\Handover;
use App\Models\RealizationPackage;
use App\Models\User;

class HandoverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR', 'PEMOHON']);
    }

    public function view(User $user, Handover $handover): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR'])) {
            return true;
        }

        return $handover->package?->proposal?->applicant_id === $user->id;
    }

    public function create(User $user, ?RealizationPackage $package = null): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        if (! $user->hasRole('PEMOHON') || ! $package) {
            return false;
        }

        return $package->proposal?->applicant_id === $user->id;
    }

    public function submit(User $user, Handover $handover): bool
    {
        if ($handover->status !== HandoverStatus::DRAFT) {
            return false;
        }

        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $handover->package?->proposal?->applicant_id === $user->id;
    }

    public function complete(User $user, Handover $handover): bool
    {
        if ($handover->created_by === $user->id) {
            return false;
        }

        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR']);
    }

    public function cancel(User $user, Handover $handover): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $handover->package?->proposal?->applicant_id === $user->id
            && $handover->status === HandoverStatus::DRAFT;
    }
}

