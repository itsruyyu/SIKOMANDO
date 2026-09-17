<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class RecommendationPolicy
{
    public function view(User $user, Proposal $proposal): bool
    {
        if ($this->isAdministrator($user)
            || $this->isAuditor($user)
            || $this->isApprover($user)) {
            return true;
        }

        if ($this->isPemohon($user)) {
            return (string) $proposal->applicant_id === (string) $user->id;
        }

        return false;
    }

    private function isPemohon(User $user): bool
    {
        return $user->hasRole('PEMOHON') || $user->hasRole('pemohon');
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN_SIKOMANDO',
            'super_admin',
            'admin_sikomando',
        ]);
    }

    private function isAuditor(User $user): bool
    {
        return $user->hasRole('AUDITOR')
            || $user->hasRole('auditor')
            || $user->hasPermission('audit.view');
    }

    private function isApprover(User $user): bool
    {
        return $user->hasRole('APPROVER')
            || $user->hasRole('approver')
            || $user->hasPermission('proposal.recommend')
            || $user->hasPermission('proposal.approve');
    }
}
