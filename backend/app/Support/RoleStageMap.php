<?php

namespace App\Support;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RoleStageMap
{
    /**
     * Stages visible to Approver (Decision Maker)
     */
    public const APPROVER_STAGES = [
        'recommended',
        'approval',
        'approved',
        'rejected',
        'disbursed',
        'implementation',
        'lpj_submitted',
        'lpj_verified',
        'completed',
    ];

    /**
     * Apply proposal query scoping based on the user's assigned roles and assignments.
     */
    public static function applyProposalVisibility(Builder $query, User $user): Builder
    {
        // 1. Super Admin & Admin SIKOMANDO see everything
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return $query;
        }

        // 2. Build role-based OR conditions
        return $query->where(function (Builder $q) use ($user) {
            $hasCondition = false;

            // Pemohon (Applicant)
            if ($user->hasRole('PEMOHON')) {
                $hasCondition = true;
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->where('applicant_id', $user->id)
                        ->orWhereHas('organization.users', function ($orgUserQuery) use ($user) {
                            $orgUserQuery->where('users.id', $user->id);
                        });
                });
            }

            // Verifikator (Only proposals assigned to this verifikator by Admin)
            if ($user->hasRole('VERIFIKATOR')) {
                $hasCondition = true;
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->whereHas('assignments', function ($a) use ($user) {
                        $a->where('assigned_user_id', $user->id)
                            ->where('assignment_type', AssignmentType::VERIFICATION->value)
                            ->whereIn('status', [
                                AssignmentStatus::ASSIGNED->value,
                                AssignmentStatus::IN_PROGRESS->value,
                                AssignmentStatus::COMPLETED->value,
                            ]);
                    });
                });
            }

            // Evaluator (Only proposals assigned to this evaluator by Admin)
            if ($user->hasRole('EVALUATOR')) {
                $hasCondition = true;
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->whereHas('assignments', function ($a) use ($user) {
                        $a->where('assigned_user_id', $user->id)
                            ->where('assignment_type', AssignmentType::EVALUATION->value)
                            ->whereIn('status', [
                                AssignmentStatus::ASSIGNED->value,
                                AssignmentStatus::IN_PROGRESS->value,
                                AssignmentStatus::COMPLETED->value,
                            ]);
                    });
                });
            }

            // Surveyor (Only proposals assigned to this surveyor by Admin or in field survey)
            if ($user->hasRole('SURVEYOR')) {
                $hasCondition = true;
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->whereHas('assignments', function ($a) use ($user) {
                        $a->where('assigned_user_id', $user->id)
                            ->where('assignment_type', AssignmentType::FIELD_SURVEY->value)
                            ->whereIn('status', [
                                AssignmentStatus::ASSIGNED->value,
                                AssignmentStatus::IN_PROGRESS->value,
                                AssignmentStatus::COMPLETED->value,
                            ]);
                    })->orWhereHas('fieldSurveys', function ($fs) use ($user) {
                        $fs->where('surveyor_id', $user->id);
                    });
                });
            }

            // Approver (Proposals in review, approval, decision, disbursement, completed)
            if ($user->hasRole('APPROVER')) {
                $hasCondition = true;
                $q->orWhereIn('status', self::APPROVER_STAGES);
            }

            // Auditor (Read-only on submitted and progressed proposals)
            if ($user->hasRole('AUDITOR')) {
                $hasCondition = true;
                $q->orWhere('status', '!=', 'draft');
            }

            // Fallback: If user has none of the recognized roles, return empty
            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Check if a specific proposal is visible to the given user.
     */
    public static function isProposalVisibleTo(Proposal $proposal, User $user): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        return Proposal::query()
            ->whereKey($proposal->id)
            ->visibleTo($user)
            ->exists();
    }
}

