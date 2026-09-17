<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalWorkflowService
{
    private const TRANSITIONS = [
        'draft' => [
            'submitted',
            'cancelled',
        ],
        'submitted' => [
            'verification',
            'cancelled',
        ],
        'verification' => [
            'revision',
            'verified',
            'rejected',
        ],
        'revision' => [
            'submitted',
            'lpj_submitted',
            'cancelled',
        ],
        'verified' => [
            'evaluation',
            'rejected',
        ],
        'evaluation' => [
            'survey',
            'recommended',
            'rejected',
        ],
        'survey' => [
            'recommended',
            'rejected',
        ],
        'recommended' => [
            'approval',
            'rejected',
        ],
        'approval' => [
            'approved',
            'rejected',
        ],
        'approved' => [
            'disbursed',
            'cancelled',
        ],
        'disbursed' => [
            'implementation',
            'lpj_submitted',
        ],
        'implementation' => [
            'lpj_submitted',
            'cancelled',
        ],
        'lpj_submitted' => [
            'lpj_verified',
            'revision',
            'completed',
        ],
        'lpj_verified' => [
            'completed',
            'revision',
        ],
        'completed' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    public function transition(
        Proposal $proposal,
        ProposalStatus $targetStatus,
        string $actorId,
        ?string $reason = null,
        ?string $notes = null,
        ?string $requestId = null,
    ): Proposal {
        $currentStatus = $proposal->status instanceof ProposalStatus
            ? $proposal->status
            : ProposalStatus::from($proposal->status);

        if ($currentStatus === $targetStatus) {
            throw ValidationException::withMessages([
                'status' => 'Proposal sudah berada pada status tersebut.',
            ]);
        }

        $allowedTransitions = self::TRANSITIONS[$currentStatus->value] ?? [];

        if (! in_array($targetStatus->value, $allowedTransitions, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transisi dari %s ke %s tidak diperbolehkan.',
                    $currentStatus->value,
                    $targetStatus->value
                ),
            ]);
        }

        if (
            in_array($targetStatus, [
                ProposalStatus::REJECTED,
                ProposalStatus::CANCELLED,
            ], true)
            && blank($reason)
        ) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan wajib diisi untuk penolakan atau pembatalan.',
            ]);
        }

        return DB::transaction(function () use (
            $proposal,
            $currentStatus,
            $targetStatus,
            $actorId,
            $reason,
            $notes,
            $requestId
        ): Proposal {
            $proposal->status = $targetStatus;

            if ($targetStatus === ProposalStatus::SUBMITTED) {
                $proposal->submitted_at = now();
            }

            if ($targetStatus === ProposalStatus::VERIFIED) {
                $proposal->verified_at = now();
            }

            if ($targetStatus === ProposalStatus::APPROVED) {
                $proposal->approved_at = now();
            }

            if ($targetStatus === ProposalStatus::COMPLETED) {
                $proposal->completed_at = now();
            }

            $proposal->save();

            $proposal->statusHistories()->create([
                'from_status' => $currentStatus->value,
                'to_status' => $targetStatus->value,
                'changed_by' => $actorId,
                'reason' => $reason,
                'notes' => $notes,
                'request_id' => $requestId,
                'changed_at' => now(),
            ]);

            return $proposal->refresh();
        });
    }

    public function canTransition(
        ProposalStatus $currentStatus,
        ProposalStatus $targetStatus
    ): bool {
        return in_array(
            $targetStatus->value,
            self::TRANSITIONS[$currentStatus->value] ?? [],
            true
        );
    }

    public function availableTransitions(
        ProposalStatus $currentStatus
    ): array {
        return array_map(
            fn (string $status): ProposalStatus => ProposalStatus::from($status),
            self::TRANSITIONS[$currentStatus->value] ?? []
        );
    }
}
