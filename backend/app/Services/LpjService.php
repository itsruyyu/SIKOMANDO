<?php

namespace App\Services;

use App\Enums\DecisionResult;
use App\Enums\DisbursementStatus;
use App\Enums\LpjItemStatus;
use App\Enums\LpjStatus;
use App\Enums\ProposalStatus;
use App\Models\Disbursement;
use App\Models\LpjDocument;
use App\Models\LpjItem;
use App\Models\LpjSubmission;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LpjService
{
    public function __construct(
        protected ProposalWorkflowService $proposalWorkflowService,
        protected NumberingService $numberingService,
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService
    ) {}

    public function validateLpjPrerequisites(Proposal $proposal): float
    {
        $totalReceived = (float) Disbursement::query()
            ->where('proposal_id', $proposal->id)
            ->where('status', DisbursementStatus::PAID)
            ->sum('paid_amount');

        $statusValue = $proposal->status instanceof ProposalStatus
            ? $proposal->status->value
            : (string) $proposal->status;

        $isDisbursedOrLater = in_array($statusValue, [
            ProposalStatus::DISBURSED->value,
            ProposalStatus::IMPLEMENTATION->value,
            ProposalStatus::LPJ_SUBMITTED->value,
            ProposalStatus::LPJ_VERIFIED->value,
            ProposalStatus::COMPLETED->value,
        ], true);

        if ($totalReceived <= 0 && ! $isDisbursedOrLater) {
            throw ValidationException::withMessages([
                'proposal' => 'LPJ tidak dapat dibuat sebelum ada pencairan dana yang sah.',
            ]);
        }

        return $totalReceived > 0
            ? $totalReceived
            : (float) ($proposal->approved_amount ?: $proposal->requested_amount);
    }

    public function createSubmission(Proposal $proposal, User $actor, array $data): LpjSubmission
    {
        $totalReceived = $this->validateLpjPrerequisites($proposal);

        $items = $data['items'] ?? [];
        $totalSpent = 0;

        foreach ($items as $item) {
            $realized = isset($item['realized_amount'])
                ? (float) $item['realized_amount']
                : (float) ($item['subtotal'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0)));
            $totalSpent += $realized;
        }

        if ($totalSpent > $totalReceived) {
            throw ValidationException::withMessages([
                'total_spent' => sprintf(
                    'Total realisasi pengeluaran (Rp %s) melebihi total dana yang telah dicairkan (Rp %s).',
                    number_format($totalSpent, 0, ',', '.'),
                    number_format($totalReceived, 0, ',', '.')
                ),
            ]);
        }

        return DB::transaction(function () use ($proposal, $actor, $data, $items, $totalReceived, $totalSpent) {
            $lpjNumber = $this->numberingService->generateNumber('lpj', $proposal->grantProgram);

            $decision = $proposal->decisions()
                ->where('result', DecisionResult::APPROVED)
                ->latest('issued_at')
                ->first();

            $submission = LpjSubmission::create([
                'proposal_id' => $proposal->id,
                'organization_id' => $proposal->organization_id,
                'grant_program_id' => $proposal->grant_program_id,
                'decision_id' => $decision?->id,
                'disbursement_id' => $data['disbursement_id'] ?? null,
                'stage_number' => $data['stage_number'] ?? null,
                'submitted_by' => $actor->id,
                'lpj_number' => $lpjNumber,
                'status' => LpjStatus::DRAFT,
                'total_received' => $totalReceived,
                'total_spent' => $totalSpent,
                'remaining_balance' => max(0.0, $totalReceived - $totalSpent),
                'summary' => $data['summary'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $planned = isset($item['planned_amount']) ? (float) $item['planned_amount'] : ($qty * $unitPrice);
                $realized = isset($item['realized_amount']) ? (float) $item['realized_amount'] : ($qty * $unitPrice);
                $variance = $planned - $realized;

                LpjItem::create([
                    'lpj_submission_id' => $submission->id,
                    'proposal_budget_item_id' => $item['proposal_budget_item_id'] ?? null,
                    'category' => $item['category'] ?? 'Operasional',
                    'budget_item' => $item['budget_item'] ?? $item['item_name'] ?? 'Item Pengeluaran',
                    'item_name' => $item['item_name'] ?? 'Item Pengeluaran',
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? 'unit',
                    'unit_price' => $unitPrice,
                    'subtotal' => $realized,
                    'planned_amount' => $planned,
                    'realized_amount' => $realized,
                    'variance' => $variance,
                    'status' => LpjItemStatus::REPORTED,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            if (! empty($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    LpjDocument::create([
                        'lpj_submission_id' => $submission->id,
                        'uploaded_by' => $actor->id,
                        'document_type' => $doc['document_type'] ?? 'financial_report',
                        'document_title' => $doc['document_title'] ?? 'Dokumen Bukti LPJ',
                        'original_filename' => $doc['original_filename'] ?? 'dokumen.pdf',
                        'stored_filename' => $doc['stored_filename'] ?? 'lpj_'.Str::random(12).'.pdf',
                        'disk' => $doc['disk'] ?? 'private',
                        'storage_path' => $doc['storage_path'] ?? 'lpj/documents/'.Str::random(12).'.pdf',
                        'mime_type' => $doc['mime_type'] ?? 'application/pdf',
                        'file_size' => $doc['file_size'] ?? 102400,
                        'notes' => $doc['notes'] ?? null,
                    ]);
                }
            }

            $this->auditLogService->record(
                action: 'lpj.created',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $submission->id,
                newValues: [
                    'lpj_number' => $lpjNumber,
                    'proposal_id' => $proposal->id,
                    'total_received' => $totalReceived,
                    'total_spent' => $totalSpent,
                ]
            );

            return $submission->fresh(['proposal.organization', 'items', 'documents', 'submittedBy:id,name,email']);
        });
    }

    public function updateSubmission(LpjSubmission $lpj, User $actor, array $data): LpjSubmission
    {
        if (! $lpj->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ sudah tidak dapat diubah karena telah diproses atau berstatus final.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $data) {
            if (isset($data['summary'])) {
                $lpj->summary = $data['summary'];
            }
            if (isset($data['notes'])) {
                $lpj->notes = $data['notes'];
            }

            if (isset($data['items']) && is_array($data['items'])) {
                $totalSpent = 0;
                $lpj->items()->delete();

                foreach ($data['items'] as $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $planned = isset($item['planned_amount']) ? (float) $item['planned_amount'] : ($qty * $unitPrice);
                    $realized = isset($item['realized_amount']) ? (float) $item['realized_amount'] : ($qty * $unitPrice);
                    $variance = $planned - $realized;
                    $totalSpent += $realized;

                    LpjItem::create([
                        'lpj_submission_id' => $lpj->id,
                        'proposal_budget_item_id' => $item['proposal_budget_item_id'] ?? null,
                        'category' => $item['category'] ?? 'Operasional',
                        'budget_item' => $item['budget_item'] ?? $item['item_name'] ?? 'Item Pengeluaran',
                        'item_name' => $item['item_name'] ?? 'Item Pengeluaran',
                        'description' => $item['description'] ?? null,
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'unit',
                        'unit_price' => $unitPrice,
                        'subtotal' => $realized,
                        'planned_amount' => $planned,
                        'realized_amount' => $realized,
                        'variance' => $variance,
                        'status' => LpjItemStatus::REPORTED,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                if ($totalSpent > (float) $lpj->total_received) {
                    throw ValidationException::withMessages([
                        'total_spent' => sprintf(
                            'Total realisasi pengeluaran (Rp %s) melebihi total dana yang telah dicairkan (Rp %s).',
                            number_format($totalSpent, 0, ',', '.'),
                            number_format((float) $lpj->total_received, 0, ',', '.')
                        ),
                    ]);
                }

                $lpj->total_spent = $totalSpent;
                $lpj->remaining_balance = max(0.0, (float) $lpj->total_received - $totalSpent);
            }

            $lpj->save();

            $this->auditLogService->record(
                action: 'lpj.updated',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'total_spent' => $lpj->total_spent,
                    'remaining_balance' => $lpj->remaining_balance,
                ]
            );

            return $lpj->fresh(['proposal.organization', 'items', 'documents', 'submittedBy:id,name,email']);
        });
    }

    public function submitSubmission(LpjSubmission $lpj, User $actor, array $data = []): LpjSubmission
    {
        if (! $lpj->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ hanya dapat diajukan saat berstatus draf atau perlu revisi.',
            ]);
        }

        if ($lpj->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'LPJ wajib memiliki minimal 1 item realisasi anggaran sebelum diajukan.',
            ]);
        }

        if ((float) $lpj->total_spent <= 0) {
            throw ValidationException::withMessages([
                'total_spent' => 'Total realisasi pengeluaran LPJ harus lebih besar dari 0.',
            ]);
        }

        if ($lpj->documents()->count() === 0) {
            throw ValidationException::withMessages([
                'documents' => 'LPJ wajib mengunggah minimal 1 dokumen bukti pertanggungjawaban fisik/keuangan.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $actor) {
            $lpj->status = LpjStatus::SUBMITTED;
            $lpj->submitted_at = now();
            $lpj->save();

            $proposal = $lpj->proposal;
            $proposalStatus = $proposal->status instanceof ProposalStatus
                ? $proposal->status
                : ProposalStatus::tryFrom((string) $proposal->status);

            if ($proposalStatus !== ProposalStatus::LPJ_SUBMITTED) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::LPJ_SUBMITTED,
                    actorId: $actor->id,
                    reason: 'Laporan Pertanggungjawaban (LPJ) resmi telah diajukan oleh pemohon.',
                );
            }

            $this->auditLogService->record(
                action: 'lpj.submitted',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::SUBMITTED->value,
                    'submitted_at' => $lpj->submitted_at,
                ]
            );

            // Notify verifiers / approvers
            $reviewers = User::whereHas('roles', function ($q) {
                $q->whereIn('code', ['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'VERIFIKATOR', 'APPROVER']);
            })->get();

            $this->notificationService->createForMany(
                recipients: $reviewers,
                type: 'lpj_submitted',
                title: 'Pengajuan LPJ Baru',
                message: sprintf('LPJ untuk proposal "%s" telah diajukan dan siap diverifikasi.', $proposal->title),
                entityType: LpjSubmission::class,
                entityId: $lpj->id
            );

            return $lpj->fresh(['proposal.organization', 'items', 'documents', 'submittedBy:id,name,email']);
        });
    }

    public function reviewSubmission(LpjSubmission $lpj, User $actor, array $data): LpjSubmission
    {
        if (! $lpj->status->canBeReviewed()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ tidak dalam status yang dapat ditinjau.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $actor, $data) {
            $lpj->status = LpjStatus::UNDER_REVIEW;
            $lpj->verified_by = $actor->id;
            $lpj->verified_at = now();
            $lpj->verification_notes = $data['notes'] ?? $data['verification_notes'] ?? 'Sedang dalam peninjauan verifikator.';
            $lpj->save();

            $this->auditLogService->record(
                action: 'lpj.reviewed',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::UNDER_REVIEW->value,
                    'verified_by' => $actor->id,
                ]
            );

            return $lpj->fresh(['proposal.organization', 'items', 'documents', 'verifiedBy:id,name,email']);
        });
    }

    public function requestRevision(LpjSubmission $lpj, User $actor, array $data): LpjSubmission
    {
        $reason = $data['reason'] ?? $data['notes'] ?? null;
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan permintaan revisi LPJ wajib diisi.',
            ]);
        }

        if ($lpj->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ yang berstatus final tidak dapat diminta revisi.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $actor, $reason) {
            $lpj->status = LpjStatus::REVISION_REQUESTED;
            $lpj->revision_reason = $reason;
            $lpj->save();

            $proposal = $lpj->proposal;
            $proposalStatus = $proposal->status instanceof ProposalStatus
                ? $proposal->status
                : ProposalStatus::tryFrom((string) $proposal->status);

            if ($proposalStatus !== ProposalStatus::REVISION) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::REVISION,
                    actorId: $actor->id,
                    reason: 'Permintaan perbaikan/revisi berkas LPJ.',
                    notes: $reason
                );
            }

            $this->auditLogService->record(
                action: 'lpj.revision_requested',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::REVISION_REQUESTED->value,
                    'reason' => $reason,
                ]
            );

            if ($lpj->submittedBy) {
                $this->notificationService->create(
                    recipient: $lpj->submittedBy,
                    type: 'lpj_revision_requested',
                    title: 'Permintaan Perbaikan LPJ',
                    message: sprintf('LPJ Anda untuk proposal "%s" memerlukan perbaikan: %s', $proposal->title, $reason),
                    entityType: LpjSubmission::class,
                    entityId: $lpj->id
                );
            }

            return $lpj->fresh(['proposal.organization', 'items', 'documents']);
        });
    }

    public function approveSubmission(LpjSubmission $lpj, User $actor, array $data = []): LpjSubmission
    {
        if ($lpj->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ sudah berstatus final dan tidak dapat disetujui kembali.',
            ]);
        }

        // Maker-Checker enforcement
        if ($lpj->submitted_by && $lpj->submitted_by === $actor->id) {
            throw ValidationException::withMessages([
                'actor' => 'Maker tidak dapat melakukan approval atas pengajuan LPJ sendiri (Maker-Checker violation).',
            ]);
        }

        return DB::transaction(function () use ($lpj, $actor, $data) {
            $lpj->status = LpjStatus::APPROVED;
            $lpj->approved_by = $actor->id;
            $lpj->approved_at = now();
            if (! empty($data['notes'])) {
                $lpj->notes = $data['notes'];
            }
            $lpj->save();

            $proposal = $lpj->proposal;
            $proposalStatus = $proposal->status instanceof ProposalStatus
                ? $proposal->status
                : ProposalStatus::tryFrom((string) $proposal->status);

            if ($proposalStatus !== ProposalStatus::LPJ_VERIFIED) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::LPJ_VERIFIED,
                    actorId: $actor->id,
                    reason: 'LPJ telah disetujui dan diverifikasi lengkap oleh pejabat berwenang.',
                );
            }

            $this->auditLogService->record(
                action: 'lpj.approved',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::APPROVED->value,
                    'approved_by' => $actor->id,
                ]
            );

            if ($lpj->submittedBy) {
                $this->notificationService->create(
                    recipient: $lpj->submittedBy,
                    type: 'lpj_approved',
                    title: 'LPJ Disetujui',
                    message: sprintf('LPJ untuk proposal "%s" telah disetujui oleh pejabat berwenang.', $proposal->title),
                    entityType: LpjSubmission::class,
                    entityId: $lpj->id
                );
            }

            return $lpj->fresh(['proposal.organization', 'items', 'documents', 'approvedBy:id,name,email']);
        });
    }

    public function rejectSubmission(LpjSubmission $lpj, User $actor, array $data): LpjSubmission
    {
        $reason = $data['reason'] ?? null;
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan LPJ wajib diisi.',
            ]);
        }

        if ($lpj->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'LPJ yang berstatus final tidak dapat ditolak.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $reason) {
            $lpj->status = LpjStatus::REJECTED;
            $lpj->rejection_reason = $reason;
            $lpj->save();

            $this->auditLogService->record(
                action: 'lpj.rejected',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::REJECTED->value,
                    'reason' => $reason,
                ]
            );

            if ($lpj->submittedBy) {
                $this->notificationService->create(
                    recipient: $lpj->submittedBy,
                    type: 'lpj_rejected',
                    title: 'LPJ Ditolak',
                    message: sprintf('LPJ untuk proposal "%s" ditolak: %s', $lpj->proposal->title, $reason),
                    entityType: LpjSubmission::class,
                    entityId: $lpj->id
                );
            }

            return $lpj->fresh(['proposal.organization', 'items', 'documents']);
        });
    }

    public function finalizeSubmission(LpjSubmission $lpj, User $actor, array $data = []): LpjSubmission
    {
        if ($lpj->status !== LpjStatus::APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya LPJ yang telah disetujui (APPROVED) yang dapat difinalisasi.',
            ]);
        }

        return DB::transaction(function () use ($lpj, $actor) {
            $lpj->status = LpjStatus::FINALIZED;
            $lpj->save();

            $this->auditLogService->record(
                action: 'lpj.finalized',
                module: 'lpj',
                entityType: LpjSubmission::class,
                entityId: $lpj->id,
                newValues: [
                    'status' => LpjStatus::FINALIZED->value,
                    'finalized_by' => $actor->id,
                ]
            );

            return $lpj->fresh(['proposal.organization', 'items', 'documents']);
        });
    }

    public function addDocument(LpjSubmission $lpj, User $actor, array $data): LpjDocument
    {
        if (! $lpj->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Dokumen bukti hanya dapat ditambahkan saat LPJ berstatus draf atau perlu revisi.',
            ]);
        }

        return LpjDocument::create([
            'lpj_submission_id' => $lpj->id,
            'uploaded_by' => $actor->id,
            'document_type' => $data['document_type'] ?? 'financial_report',
            'document_title' => $data['document_title'] ?? 'Dokumen Bukti LPJ',
            'original_filename' => $data['original_filename'] ?? 'dokumen.pdf',
            'stored_filename' => $data['stored_filename'] ?? 'lpj_'.Str::random(12).'.pdf',
            'disk' => $data['disk'] ?? 'private',
            'storage_path' => $data['storage_path'] ?? 'lpj/documents/'.Str::random(12).'.pdf',
            'mime_type' => $data['mime_type'] ?? 'application/pdf',
            'file_size' => $data['file_size'] ?? 102400,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function getClosingSummary(Proposal $proposal): array
    {
        // 1. Check unpaid disbursements
        $unpaidDisbursements = Disbursement::query()
            ->where('proposal_id', $proposal->id)
            ->whereNotIn('status', [DisbursementStatus::PAID, DisbursementStatus::CANCELLED])
            ->count();

        $totalDisbursed = (float) Disbursement::query()
            ->where('proposal_id', $proposal->id)
            ->where('status', DisbursementStatus::PAID)
            ->sum('paid_amount');

        // 2. Check LPJs
        $lpjs = LpjSubmission::query()->where('proposal_id', $proposal->id)->get();
        $lpjCount = $lpjs->count();
        $approvedLpjs = $lpjs->filter(fn ($l) => in_array($l->status, [LpjStatus::APPROVED, LpjStatus::FINALIZED, LpjStatus::CLOSED], true));
        $unapprovedLpjs = $lpjs->filter(fn ($l) => ! in_array($l->status, [LpjStatus::APPROVED, LpjStatus::FINALIZED, LpjStatus::CLOSED], true));

        $totalSpent = (float) $approvedLpjs->sum('total_spent');

        // 3. Open revisions
        $openRevisions = $proposal->revisions()->where('status', '!=', 'completed')->count();

        // 4. Determine blockers
        $blockers = [];

        if ($totalDisbursed <= 0) {
            $blockers[] = 'Proposal belum memiliki riwayat pencairan dana yang terbayar.';
        }

        if ($unpaidDisbursements > 0) {
            $blockers[] = 'Masih terdapat tahap pencairan dana yang belum selesai diproses.';
        }

        if ($lpjCount === 0) {
            $blockers[] = 'Proposal belum memiliki Laporan Pertanggungjawaban (LPJ).';
        }

        if ($unapprovedLpjs->count() > 0) {
            $blockers[] = 'Terdapat LPJ yang belum disetujui atau difinalisasi.';
        }

        if ($openRevisions > 0) {
            $blockers[] = 'Masih terdapat permintaan revisi terbuka yang belum diselesaikan.';
        }

        $isEligible = empty($blockers);

        return [
            'proposal_id' => $proposal->id,
            'proposal_number' => $proposal->proposal_number,
            'proposal_title' => $proposal->title,
            'status' => $proposal->status instanceof ProposalStatus ? $proposal->status->value : (string) $proposal->status,
            'status_label' => $proposal->status instanceof ProposalStatus ? $proposal->status->label() : (string) $proposal->status,
            'approved_ceiling' => (float) ($proposal->approved_amount ?: $proposal->requested_amount),
            'total_disbursed' => $totalDisbursed,
            'total_spent' => $totalSpent,
            'remaining_balance' => max(0.0, $totalDisbursed - $totalSpent),
            'total_lpj_count' => $lpjCount,
            'approved_lpj_count' => $approvedLpjs->count(),
            'unapproved_lpj_count' => $unapprovedLpjs->count(),
            'open_revisions_count' => $openRevisions,
            'is_eligible_for_close' => $isEligible,
            'blockers' => $blockers,
        ];
    }

    public function closeProposal(Proposal $proposal, User $actor, array $data = []): Proposal
    {
        $summary = $this->getClosingSummary($proposal);

        if (! $summary['is_eligible_for_close']) {
            throw ValidationException::withMessages([
                'closing' => $summary['blockers'],
            ]);
        }

        return DB::transaction(function () use ($proposal, $actor, $data) {
            // Close all approved/finalized LPJs
            LpjSubmission::query()
                ->where('proposal_id', $proposal->id)
                ->whereIn('status', [LpjStatus::APPROVED, LpjStatus::FINALIZED])
                ->update([
                    'status' => LpjStatus::CLOSED->value,
                    'closed_by' => $actor->id,
                    'closed_at' => now(),
                ]);

            // Transition proposal to COMPLETED
            $this->proposalWorkflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::COMPLETED,
                actorId: $actor->id,
                reason: 'Seluruh tahapan pencairan dan pertanggungjawaban dana (LPJ) telah selesai dan disetujui.',
                notes: $data['notes'] ?? null
            );

            $this->auditLogService->record(
                action: 'proposal.closed',
                module: 'closing',
                entityType: Proposal::class,
                entityId: $proposal->id,
                newValues: [
                    'status' => ProposalStatus::COMPLETED->value,
                    'closed_by' => $actor->id,
                    'closed_at' => now(),
                ]
            );

            if ($proposal->applicant) {
                $this->notificationService->create(
                    recipient: $proposal->applicant,
                    type: 'proposal_closed',
                    title: 'Proposal Selesai & Ditutup',
                    message: sprintf('Program hibah untuk proposal "%s" telah resmi ditutup dengan status selesai.', $proposal->title),
                    entityType: Proposal::class,
                    entityId: $proposal->id
                );
            }

            return $proposal->fresh(['organization', 'lpjSubmissions', 'disbursements']);
        });
    }

    public function paginateLpjs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LpjSubmission::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,status,requested_amount,approved_amount',
                'organization:id,name',
                'grantProgram:id,code,name,fiscal_year',
                'submittedBy:id,name,email',
                'items',
                'documents',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['proposal_id'])) {
            $query->where('proposal_id', $filters['proposal_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function findLpj(string $id): LpjSubmission
    {
        return LpjSubmission::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,status,requested_amount,approved_amount',
                'organization:id,name,address,phone,email',
                'grantProgram:id,code,name,fiscal_year',
                'decision:id,decision_number,title',
                'disbursement',
                'submittedBy:id,name,email',
                'verifiedBy:id,name,email',
                'approvedBy:id,name,email',
                'closedBy:id,name,email',
                'items.budgetItem',
                'documents.uploader:id,name,email',
            ])
            ->findOrFail($id);
    }
}
