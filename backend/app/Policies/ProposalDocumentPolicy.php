<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;

class ProposalDocumentPolicy
{
    public function viewAny(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return $proposal->applicant_id === $user->id
                || $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();
        }

        if ($user->hasRole('AUDITOR')) {
            return true;
        }

        return $user->hasAnyRole([
            'ADMIN_SIKOMANDO',
            'VERIFIKATOR',
            'EVALUATOR',
            'SURVEYOR',
            'APPROVER',
        ]) || $user->hasPermission('proposal.view');
    }

    public function view(User $user, ProposalDocument $document): bool
    {
        if ($user->hasRole('SUPER_ADMIN')) {
            return true;
        }

        $proposal = $document->proposal;
        if (! $proposal) {
            return false;
        }

        if ($user->hasRole('PEMOHON')) {
            return $proposal->applicant_id === $user->id
                || $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();
        }

        if ($user->hasRole('AUDITOR')) {
            return true;
        }

        return $user->hasAnyRole([
            'ADMIN_SIKOMANDO',
            'VERIFIKATOR',
            'EVALUATOR',
            'SURVEYOR',
            'APPROVER',
        ]) || $user->hasPermission('proposal.view');
    }

    public function create(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('ADMIN_SIKOMANDO')) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return ($proposal->applicant_id === $user->id || $user->organizations()->where('organizations.id', $proposal->organization_id)->exists())
                && in_array($proposal->status->value, ['draft', 'submitted', 'revision'], true);
        }

        return $user->hasPermission('proposal.update');
    }

    public function update(User $user, ProposalDocument $document): bool
    {
        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('ADMIN_SIKOMANDO')) {
            return true;
        }

        $proposal = $document->proposal;
        if (! $proposal) {
            return false;
        }

        if ($user->hasRole('PEMOHON')) {
            $isOwner = $proposal->applicant_id === $user->id
                || $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();

            if (! $isOwner) {
                return false;
            }

            if ($document->status === 'verified' && $proposal->status->value !== 'revision') {
                return false;
            }

            return in_array($proposal->status->value, ['draft', 'submitted', 'revision'], true);
        }

        return $user->hasPermission('proposal.update');
    }

    public function delete(User $user, ProposalDocument $document): bool
    {
        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('ADMIN_SIKOMANDO')) {
            return true;
        }

        $proposal = $document->proposal;
        if (! $proposal) {
            return false;
        }

        if ($user->hasRole('PEMOHON')) {
            $isOwner = $proposal->applicant_id === $user->id
                || $user->organizations()->where('organizations.id', $proposal->organization_id)->exists();

            if (! $isOwner) {
                return false;
            }

            if ($document->status === 'verified') {
                return false;
            }

            return in_array($proposal->status->value, ['draft', 'revision'], true);
        }

        return false;
    }

    public function download(User $user, ProposalDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
