<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevisionService
{
    public function __construct(
        private readonly ProposalWorkflowService $workflowService,
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function create(
        Proposal $proposal,
        User $actor,
        string $reason,
        array $items,
        ?string $requestId = null,
    ): Revision {
        if (! in_array(
            $proposal->status->value,
            [
                ProposalStatus::VERIFICATION->value,
                ProposalStatus::EVALUATION->value,
                ProposalStatus::SURVEY->value,
                ProposalStatus::LPJ_SUBMITTED->value,
                ProposalStatus::LPJ_VERIFIED->value,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'proposal' => [
                    'Proposal tidak berada pada status yang dapat direvisi.',
                ],
            ]);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => [
                    'Alasan revisi wajib diisi.',
                ],
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => [
                    'Minimal satu item revisi wajib diisi.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $proposal,
            $actor,
            $reason,
            $items,
            $requestId
        ): Revision {
            $revisionNumber = (
                (int) Revision::query()
                    ->where('proposal_id', $proposal->id)
                    ->max('revision_number')
            ) + 1;

            $revision = Revision::query()->create([
                'proposal_id' => $proposal->id,
                'requested_by' => $actor->id,
                'revision_number' => $revisionNumber,
                'status' => 'requested',
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                $revision->items()->create([
                    'item_code' => $item['item_code'] ?? null,
                    'field_name' => $item['field_name'] ?? null,
                    'description' => $item['description'],
                    'old_value' => $item['old_value'] ?? null,
                    'new_value' => $item['new_value'] ?? null,
                    'status' => 'open',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->workflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::REVISION,
                actorId: $actor->id,
                reason: $reason,
                notes: 'Proposal dikembalikan untuk revisi.',
                requestId: $requestId,
            );

            $this->auditLogService->record(
                action: 'revision.created',
                module: 'proposal_revision',
                entityType: Revision::class,
                entityId: $revision->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'revision_number' => $revisionNumber,
                    'reason' => $reason,
                    'items_count' => count($items),
                ],
                requestId: $requestId,
            );

            return $revision->load([
                'proposal',
                'requester',
                'items',
            ]);
        });
    }

    public function submit(
        Revision $revision,
        User $actor,
        ?string $requestId = null,
    ): Proposal {
        if ($revision->status !== 'requested') {
            throw ValidationException::withMessages([
                'revision' => [
                    'Revisi ini sudah diproses dan tidak dapat diajukan ulang.',
                ],
            ]);
        }

        if ($revision->proposal->applicant_id !== $actor->id) {
            throw ValidationException::withMessages([
                'revision' => [
                    'Hanya pemohon pemilik proposal yang dapat mengajukan revisi.',
                ],
            ]);
        }

        $hasOpenItems = $revision->items()
            ->where('status', 'open')
            ->exists();

        if ($hasOpenItems) {
            throw ValidationException::withMessages([
                'items' => [
                    'Seluruh item revisi harus diselesaikan terlebih dahulu.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $revision,
            $actor,
            $requestId
        ): Proposal {
            $revision->forceFill([
                'status' => 'submitted',
                'submitted_at' => now(),
                'completed_at' => now(),
            ])->save();

            $proposal = $revision->proposal->fresh();

            $proposal = $this->workflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::SUBMITTED,
                actorId: $actor->id,
                reason: 'Revisi proposal telah diselesaikan oleh pemohon.',
                notes: 'Proposal diajukan ulang setelah revisi.',
                requestId: $requestId,
            );

            $this->auditLogService->record(
                action: 'revision.submitted',
                module: 'proposal_revision',
                entityType: Revision::class,
                entityId: $revision->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'revision_id' => $revision->id,
                    'status' => 'submitted',
                ],
                requestId: $requestId,
            );

            $this->notificationService->create(
                recipient: $actor,
                type: 'revision.submitted',
                title: 'Revisi berhasil diajukan',
                message: sprintf(
                    'Revisi proposal "%s" dengan nomor %s berhasil diajukan kembali.',
                    $proposal->title,
                    $proposal->proposal_number,
                ),
                entityType: Revision::class,
                entityId: $revision->id,
                data: [
                    'proposal_id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                    'revision_id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'status' => 'submitted',
                ],
                requestId: $requestId,
            );

            return $proposal->fresh();
        });
    }
}
