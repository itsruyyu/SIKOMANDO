<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('proposal.viewAny');
    }

    public function view(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return $proposal->applicant_id === $user->id;
        }

        return $user->hasPermission('proposal.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('proposal.create');
    }

    public function update(User $user, Proposal $proposal): bool
    {
        if (! $user->hasPermission('proposal.update')) {
            return false;
        }

        if ($proposal->status->value !== 'draft'
            && $proposal->status->value !== 'revision') {
            return false;
        }

        return $proposal->applicant_id === $user->id
            || $user->hasRole('SUPER_ADMIN')
            || $user->hasRole('ADMIN_SIKOMANDO');
    }

    public function submit(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if (! $user->hasRole('PEMOHON')) {
            return false;
        }

        if (! $user->hasPermission('proposal.submit')) {
            return false;
        }

        return $proposal->applicant_id === $user->id
            && in_array(
                $proposal->status->value,
                ['draft', 'revision'],
                true
            );
    }

    public function verify(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.verify')
            && $proposal->status->value === 'verification';
    }

    public function evaluate(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.evaluate')
            && $proposal->status->value === 'evaluation';
    }

    public function survey(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.survey')
            && $proposal->status->value === 'survey';
    }

    public function recommend(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.recommend')
            && $proposal->status->value === 'recommended';
    }

    public function approve(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.approve')
            && $proposal->status->value === 'approval';
    }

    public function reject(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.reject');
    }

    public function disburse(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.disburse')
            && $proposal->status->value === 'approved';
    }

    public function monitor(User $user, Proposal $proposal): bool
    {
        return $user->hasPermission('proposal.monitor');
    }
}
