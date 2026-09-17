<?php

namespace App\Policies;

use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\User;

class LpjPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR']);
    }

    public function view(User $user, LpjSubmission $lpj): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR'])) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            $isApplicant = $lpj->proposal && $lpj->proposal->applicant_id === $user->id;
            $isOrgMember = $lpj->organization_id && $user->organizations()->where('organizations.id', $lpj->organization_id)->exists();

            return $isApplicant || $isOrgMember;
        }

        return false;
    }

    public function viewProposalLpjs(User $user, Proposal $proposal): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR', 'AUDITOR'])) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            $isApplicant = $proposal->applicant_id === $user->id;
            $isOrgMember = $proposal->organization_id && $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();

            return $isApplicant || $isOrgMember;
        }

        return false;
    }

    public function create(User $user, Proposal $proposal): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO'])) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            $isApplicant = $proposal->applicant_id === $user->id;
            $isOrgMember = $proposal->organization_id && $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();

            return $isApplicant || $isOrgMember;
        }

        return false;
    }

    public function update(User $user, LpjSubmission $lpj): bool
    {
        if (! $lpj->status->isEditable()) {
            return false;
        }

        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return $lpj->submitted_by === $user->id
                || ($lpj->proposal && $lpj->proposal->applicant_id === $user->id);
        }

        return false;
    }

    public function submit(User $user, LpjSubmission $lpj): bool
    {
        if (! $lpj->status->isEditable()) {
            return false;
        }

        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return $lpj->submitted_by === $user->id
                || ($lpj->proposal && $lpj->proposal->applicant_id === $user->id);
        }

        return false;
    }

    public function review(User $user, LpjSubmission $lpj): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR', 'APPROVER']);
    }

    public function requestRevision(User $user, LpjSubmission $lpj): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR', 'APPROVER']);
    }

    public function approve(User $user, LpjSubmission $lpj): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER', 'VERIFIKATOR']);
    }

    public function reject(User $user, LpjSubmission $lpj): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function finalize(User $user, LpjSubmission $lpj): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }

    public function uploadDocument(User $user, LpjSubmission $lpj): bool
    {
        return $this->update($user, $lpj);
    }

    public function close(User $user, Proposal $proposal): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'APPROVER']);
    }
}
