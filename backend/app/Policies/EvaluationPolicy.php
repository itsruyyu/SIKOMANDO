<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\Proposal;
use App\Models\User;

class EvaluationPolicy
{
    public function viewAny(User $user, Proposal $proposal): bool
    {
        return $this->isEvaluationOfficer($user)
            || $this->isAuditor($user)
            || $this->isApprover($user);
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($this->isAdministrator($user) || $this->isAuditor($user) || $this->isApprover($user)) {
            return true;
        }

        return $this->isEvaluator($user)
            && (string) $evaluation->evaluator_id === (string) $user->id;
    }

    public function create(User $user, Proposal $proposal): bool
    {
        return $this->isEvaluationOfficer($user);
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->status?->isFinal()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isEvaluator($user)
            && (string) $evaluation->evaluator_id === (string) $user->id;
    }

    public function complete(User $user, Evaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->status?->isFinal()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isEvaluator($user)
            && (string) $evaluation->evaluator_id === (string) $user->id;
    }

    private function isEvaluationOfficer(User $user): bool
    {
        return $this->isAdministrator($user)
            || $this->isEvaluator($user);
    }

    private function isEvaluator(User $user): bool
    {
        return $user->hasRole('EVALUATOR')
            || $user->hasRole('evaluator')
            || $user->hasPermission('proposal.evaluate')
            || $user->hasPermission('evaluation.view')
            || $user->hasPermission('evaluation.create')
            || $user->hasPermission('evaluation.update');
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
            || $user->hasPermission('proposal.approve');
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
}
