<?php

namespace App\Policies;

use App\Enums\DecisionStatus;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\Proposal;
use App\Models\User;

class DecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR']);
    }

    public function view(User $user, Decision $decision): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR'])) {
            return true;
        }

        // Pemohon can view their own published decision
        return $user->hasRole('PEMOHON')
            && $decision->proposal->applicant_id === $user->id
            && $decision->status === DecisionStatus::PUBLISHED;
    }

    public function viewProposalDecision(User $user, Proposal $proposal): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR'])) {
            return true;
        }

        return $user->hasRole('PEMOHON') && $proposal->applicant_id === $user->id;
    }

    public function publish(User $user, Decision $decision): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function generateDocument(User $user, Decision $decision): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function downloadDocument(User $user, Decision $decision, DecisionDocument $document): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'AUDITOR'])) {
            return true;
        }

        return $user->hasRole('PEMOHON')
            && $decision->proposal->applicant_id === $user->id
            && $decision->status === DecisionStatus::PUBLISHED;
    }
}

