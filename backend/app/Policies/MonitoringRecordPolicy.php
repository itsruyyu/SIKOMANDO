<?php

namespace App\Policies;

use App\Models\MonitoringRecord;
use App\Models\Proposal;
use App\Models\User;

class MonitoringRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'SURVEYOR', 'VERIFIKATOR', 'PEMOHON']);
    }

    public function view(User $user, MonitoringRecord $record): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'SURVEYOR', 'VERIFIKATOR'])) {
            return true;
        }

        return $record->proposal?->applicant_id === $user->id;
    }

    public function create(User $user, ?Proposal $proposal = null): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'SURVEYOR']);
    }

    public function checkItem(User $user, MonitoringRecord $record): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'SURVEYOR']);
    }

    public function complete(User $user, MonitoringRecord $record): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'SURVEYOR']);
    }
}

