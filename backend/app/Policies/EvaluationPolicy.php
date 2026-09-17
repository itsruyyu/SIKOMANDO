<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\Proposal;
use App\Models\User;

class EvaluationPolicy
{
    public function viewAny(User $user, Proposal $proposal): bool
    {
        return $this->isEvaluationOfficer($user);
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        return $user->hasRole('EVALUATOR')
            && $evaluation->evaluator_id === $user->id;
    }

    public function create(User $user, Proposal $proposal): bool
    {
        return $this->isEvaluationOfficer($user);
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        return $user->hasRole('EVALUATOR')
            && $evaluation->evaluator_id === $user->id;
    }

    public function complete(User $user, Evaluation $evaluation): bool
    {
        return $this->update($user, $evaluation);
    }

    private function isEvaluationOfficer(User $user): bool
    {
        return $this->isAdministrator($user)
            || $user->hasRole('EVALUATOR');
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN_SIKOMANDO',
        ]);
    }
}
