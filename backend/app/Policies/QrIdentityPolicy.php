<?php

namespace App\Policies;

use App\Enums\AssignmentStatus;
use App\Models\Decision;
use App\Models\Handover;
use App\Models\LpjSubmission;
use App\Models\MonitoringRecord;
use App\Models\Proposal;
use App\Models\QrIdentity;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Receipt;
use App\Models\User;

class QrIdentityPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    public function resolve(User $user, QrIdentity $qr): bool
    {
        if ($user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR'])) {
            return true;
        }

        $proposal = $this->resolveProposal($qr);
        if (! $proposal) {
            return true;
        }

        if ($user->hasRole('PEMOHON')) {
            return $proposal->applicant_id === $user->id;
        }

        // Staf internal: harus memiliki penugasan aktif pada usulan
        return $proposal->assignments()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', [AssignmentStatus::ASSIGNED, AssignmentStatus::IN_PROGRESS])
            ->exists();
    }

    protected function resolveProposal(QrIdentity $qr): ?Proposal
    {
        $entity = $qr->qrable;
        if (! $entity) {
            return null;
        }

        return match (true) {
            $entity instanceof Proposal => $entity,
            $entity instanceof Decision => $entity->proposal,
            $entity instanceof Receipt => $entity->proposal,
            $entity instanceof MonitoringRecord => $entity->proposal,
            $entity instanceof LpjSubmission => $entity->proposal,
            $entity instanceof RealizationPackage => $entity->proposal,
            $entity instanceof RealizationItem => $entity->package?->proposal,
            $entity instanceof Handover => $entity->package?->proposal,
            default => method_exists($entity, 'proposal') ? $entity->proposal : null,
        };
    }
}

