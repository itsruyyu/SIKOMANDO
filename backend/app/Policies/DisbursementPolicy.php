<?php

namespace App\Policies;

use App\Models\Disbursement;
use App\Models\Proposal;
use App\Models\User;

class DisbursementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR']);
    }

    public function view(User $user, Disbursement $disbursement): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR'])) {
            return true;
        }

        return $user->hasRole('PEMOHON') && $disbursement->proposal->applicant_id === $user->id;
    }

    public function viewSummary(User $user, Proposal $proposal): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR'])) {
            return true;
        }

        return $user->hasRole('PEMOHON') && $proposal->applicant_id === $user->id;
    }

    public function create(User $user, Proposal $proposal): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function verify(User $user, Disbursement $disbursement): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $user->hasRole('VERIFIKATOR') || $user->hasPermission('proposal.verify');
    }

    public function approve(User $user, Disbursement $disbursement): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $user->hasRole('APPROVER') || $user->hasPermission('proposal.approve');
    }

    public function process(User $user, Disbursement $disbursement): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }
}
