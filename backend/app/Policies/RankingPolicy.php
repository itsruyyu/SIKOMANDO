<?php

namespace App\Policies;

use App\Models\GrantProgram;
use App\Models\Ranking;
use App\Models\User;

class RankingPolicy
{
    public function viewAny(User $user, ?GrantProgram $grantProgram = null): bool
    {
        if ($this->isPemohon($user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->isAuditor($user)
            || $this->isApprover($user);
    }

    public function view(User $user, Ranking $ranking): bool
    {
        if ($this->isPemohon($user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->isAuditor($user)
            || $this->isApprover($user);
    }

    public function preview(User $user, GrantProgram $grantProgram): bool
    {
        if ($this->isPemohon($user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->isAuditor($user)
            || $this->isApprover($user);
    }

    public function generate(User $user, GrantProgram $grantProgram): bool
    {
        return $this->isAdministrator($user);
    }

    public function regenerate(User $user, Ranking $ranking): bool
    {
        if ($ranking->status?->isFinal()) {
            return false;
        }

        return $this->isAdministrator($user);
    }

    public function review(User $user, Ranking $ranking): bool
    {
        if ($ranking->status?->isFinal()) {
            return false;
        }

        return $this->isAdministrator($user) || $this->isApprover($user);
    }

    public function finalize(User $user, Ranking $ranking): bool
    {
        if ($ranking->status?->isFinal()) {
            return false;
        }

        return $this->isAdministrator($user);
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
