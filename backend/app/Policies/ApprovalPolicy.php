<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\Proposal;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR']);
    }

    public function view(User $user, Approval $approval): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR']);
    }

    public function create(User $user, Proposal $proposal): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function review(User $user, Approval $approval): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function approve(User $user, Approval $approval): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $user->hasRole('APPROVER') || $user->hasPermission('proposal.approve');
    }

    public function reject(User $user, Approval $approval): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return $user->hasRole('APPROVER') || $user->hasPermission('proposal.reject');
    }
}
