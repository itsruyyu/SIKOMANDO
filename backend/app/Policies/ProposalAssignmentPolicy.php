<?php

namespace App\Policies;

use App\Models\ProposalAssignment;
use App\Models\User;

class ProposalAssignmentPolicy
{
    /**
     * Determine whether the user can view any assignments.
     */
    public function viewAny(User $actor): bool
    {
        // Internal roles only; PEMOHON is explicitly blocked
        return $actor->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN_SIKOMANDO',
            'AUDITOR',
            'VERIFIKATOR',
            'EVALUATOR',
            'SURVEYOR',
            'APPROVER',
        ]);
    }

    /**
     * Determine whether the user can view the specific assignment.
     */
    public function view(User $actor, ProposalAssignment $assignment): bool
    {
        // Assigned user can see their own assignment
        if ($actor->id === $assignment->assigned_user_id) {
            return true;
        }

        // Admins and auditors can view all assignments
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR']);
    }

    /**
     * Determine whether the user can create an assignment.
     */
    public function create(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    /**
     * Determine whether the user can revoke an assignment.
     */
    public function revoke(User $actor, ProposalAssignment $assignment): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    /**
     * Determine whether the user can view workload dashboard.
     */
    public function viewWorkload(User $actor): bool
    {
        return $actor->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN_SIKOMANDO',
            'AUDITOR',
            'VERIFIKATOR',
            'EVALUATOR',
            'SURVEYOR',
        ]);
    }
}
