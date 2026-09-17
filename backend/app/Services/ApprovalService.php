<?php

namespace App\Services;

use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Enums\ProposalStatus;
use App\Enums\RankingStatus;
use App\Enums\RecommendationResult;
use App\Enums\RecommendationStatus;
use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\Decision;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        protected ProposalWorkflowService $proposalWorkflowService,
        protected NumberingService $numberingService,
        protected AuditLogService $auditLogService
    ) {}

    public function paginateApprovals(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Approval::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,requested_amount,status',
                'proposal.organization:id,name',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'recommendation:id,recommendation_number,status,result,recommended_amount',
                'decision:id,approval_id,decision_number,status,result,approved_amount,issued_at',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['proposal_id'])) {
            $query->where('proposal_id', $filters['proposal_id']);
        }

        if (! empty($filters['grant_program_id'])) {
            $query->whereHas('proposal', function ($q) use ($filters) {
                $q->where('grant_program_id', $filters['grant_program_id']);
            });
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function findApproval(string $id): Approval
    {
        return Approval::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,requested_amount,status',
                'proposal.organization:id,name',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'recommendation:id,recommendation_number,status,result,recommended_amount,summary,reason',
                'recommendation.items',
                'actions.actor:id,name,email',
                'decision.documents.versions',
                'decision.issuer:id,name,email',
            ])
            ->findOrFail($id);
    }

    public function submitForApproval(Proposal $proposal, User $actor, array $data = []): Approval
    {
        // 1. Validasi status proposal
        $currentStatus = $proposal->status instanceof ProposalStatus
            ? $proposal->status
            : ProposalStatus::from($proposal->status);

        if ($currentStatus !== ProposalStatus::RECOMMENDED) {
            throw ValidationException::withMessages([
                'proposal' => 'Hanya proposal dengan status RECOMMENDED yang dapat diajukan untuk persetujuan (approval).',
            ]);
        }

        // 2. Validasi perankingan final
        $hasFinalizedRanking = $proposal->rankingItems()
            ->whereHas('ranking', fn ($q) => $q->where('status', RankingStatus::FINALIZED))
            ->exists();

        if (! $hasFinalizedRanking) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal belum memiliki perankingan yang telah difinalisasi.',
            ]);
        }

        // 3. Validasi rekomendasi resmi
        $recommendation = $proposal->recommendations()
            ->where('status', RecommendationStatus::COMPLETED)
            ->where('result', RecommendationResult::RECOMMENDED)
            ->latest('completed_at')
            ->first();

        if ($recommendation === null) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal tidak memiliki rekomendasi resmi berstatus final (RECOMMENDED) untuk diajukan approval.',
            ]);
        }

        // 4. Validasi proses approval aktif yang belum selesai
        $existingActiveApproval = $proposal->approvals()
            ->whereNotIn('status', [ApprovalStatus::REJECTED, ApprovalStatus::CANCELLED])
            ->first();

        if ($existingActiveApproval !== null) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal sudah memiliki proses persetujuan aktif yang belum selesai.',
            ]);
        }

        return DB::transaction(function () use ($proposal, $recommendation, $actor, $data) {
            // Generate approval number
            $approvalNumber = $this->numberingService->generateNumber('approval', $proposal->grantProgram);

            // Transisi lifecycle proposal ke APPROVAL
            $this->proposalWorkflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::APPROVAL,
                actorId: $actor->id,
                reason: 'Pengajuan approval proposal.',
                notes: $data['notes'] ?? null
            );

            // Create Approval
            $approval = Approval::create([
                'proposal_id' => $proposal->id,
                'recommendation_id' => $recommendation->id,
                'approval_number' => $approvalNumber,
                'approval_type' => 'proposal',
                'required_levels' => 1,
                'current_level' => 1,
                'status' => ApprovalStatus::PENDING,
                'summary' => $data['summary'] ?? sprintf('Pengajuan persetujuan untuk proposal %s (%s)', $proposal->proposal_number, $proposal->title),
                'notes' => $data['notes'] ?? null,
                'started_at' => now(),
            ]);

            // Record action (submit)
            ApprovalAction::create([
                'approval_id' => $approval->id,
                'actor_id' => $actor->id,
                'level' => 1,
                'action' => ApprovalActionType::SUBMIT->value,
                'notes' => $data['notes'] ?? 'Pengajuan persetujuan awal.',
                'acted_at' => now(),
            ]);

            // Record audit trail
            $this->auditLogService->record(
                action: 'approval.submitted',
                module: 'approval',
                entityType: Approval::class,
                entityId: $approval->id,
                newValues: [
                    'approval_number' => $approvalNumber,
                    'proposal_id' => $proposal->id,
                    'status' => ApprovalStatus::PENDING->value,
                ]
            );

            return $approval->fresh(['proposal', 'recommendation', 'actions.actor']);
        });
    }

    public function reviewApproval(Approval $approval, User $actor, array $data = []): Approval
    {
        if ($approval->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Persetujuan sudah berstatus final dan tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($approval, $actor, $data) {
            if ($approval->status === ApprovalStatus::PENDING) {
                $approval->status = ApprovalStatus::UNDER_REVIEW;
                $approval->save();
            }

            ApprovalAction::create([
                'approval_id' => $approval->id,
                'actor_id' => $actor->id,
                'level' => $approval->current_level,
                'action' => ApprovalActionType::REVIEW->value,
                'notes' => $data['notes'] ?? 'Persetujuan sedang ditinjau.',
                'acted_at' => now(),
            ]);

            $this->auditLogService->record(
                action: 'approval.reviewed',
                module: 'approval',
                entityType: Approval::class,
                entityId: $approval->id,
                newValues: [
                    'status' => $approval->status->value,
                    'reviewed_by' => $actor->id,
                ]
            );

            return $approval->fresh(['proposal', 'recommendation', 'actions.actor']);
        });
    }

    public function approve(Approval $approval, User $actor, array $data = []): Approval
    {
        if ($approval->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Persetujuan sudah berstatus final dan tidak dapat diubah.',
            ]);
        }

        // Maker-Checker / Four-Eyes Principle check: Maker cannot approve own submission
        $submitAction = $approval->actions()
            ->where('action', ApprovalActionType::SUBMIT->value)
            ->first();

        if ($submitAction && $submitAction->actor_id === $actor->id) {
            throw ValidationException::withMessages([
                'actor' => 'Maker tidak dapat melakukan approval atas pengajuan sendiri (Maker-Checker violation).',
            ]);
        }

        return DB::transaction(function () use ($approval, $actor, $data) {
            $proposal = $approval->proposal()->with(['grantProgram', 'organization'])->firstOrFail();

            // 1. Transisi proposal ke APPROVED
            $this->proposalWorkflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::APPROVED,
                actorId: $actor->id,
                reason: 'Proposal disetujui pada tahapan persetujuan.',
                notes: $data['notes'] ?? null
            );

            // 2. Update Approval
            $approval->status = ApprovalStatus::APPROVED;
            $approval->completed_at = now();
            if (! empty($data['notes'])) {
                $approval->notes = $data['notes'];
            }
            $approval->save();

            // 3. Record Action
            ApprovalAction::create([
                'approval_id' => $approval->id,
                'actor_id' => $actor->id,
                'level' => $approval->current_level,
                'action' => ApprovalActionType::APPROVE->value,
                'notes' => $data['notes'] ?? 'Proposal disetujui.',
                'acted_at' => now(),
            ]);

            // 4. Generate Decision record
            $decisionNumber = $this->numberingService->generateNumber('decision', $proposal->grantProgram);
            $approvedAmount = isset($data['approved_amount'])
                ? (float) $data['approved_amount']
                : (float) ($approval->recommendation?->recommended_amount ?: $proposal->requested_amount);

            $decision = Decision::create([
                'proposal_id' => $proposal->id,
                'approval_id' => $approval->id,
                'issued_by' => $actor->id,
                'decision_number' => $decisionNumber,
                'decision_type' => 'grant_approval',
                'status' => DecisionStatus::PUBLISHED,
                'result' => DecisionResult::APPROVED,
                'decision_date' => now()->toDateString(),
                'approved_amount' => $approvedAmount,
                'title' => $data['title'] ?? sprintf('Penetapan Persetujuan Hibah: %s', $proposal->title),
                'summary' => $data['summary'] ?? sprintf('Persetujuan hibah untuk %s dengan nominal disetujui Rp %s.', $proposal->organization?->name ?: $proposal->title, number_format($approvedAmount, 0, ',', '.')),
                'notes' => $data['notes'] ?? null,
                'issued_at' => now(),
            ]);

            // 5. Audit Trail
            $this->auditLogService->record(
                action: 'approval.approved',
                module: 'approval',
                entityType: Approval::class,
                entityId: $approval->id,
                newValues: [
                    'status' => ApprovalStatus::APPROVED->value,
                    'decision_id' => $decision->id,
                    'decision_number' => $decisionNumber,
                    'approved_amount' => $approvedAmount,
                ]
            );

            $this->auditLogService->record(
                action: 'decision.created',
                module: 'decision',
                entityType: Decision::class,
                entityId: $decision->id,
                newValues: [
                    'decision_number' => $decisionNumber,
                    'result' => DecisionResult::APPROVED->value,
                    'approved_amount' => $approvedAmount,
                ]
            );

            return $approval->fresh(['proposal', 'recommendation', 'actions.actor', 'decision']);
        });
    }

    public function reject(Approval $approval, User $actor, array $data): Approval
    {
        if ($approval->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Persetujuan sudah berstatus final dan tidak dapat diubah.',
            ]);
        }

        if (empty($data['reason'])) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($approval, $actor, $data) {
            $proposal = $approval->proposal()->with(['grantProgram', 'organization'])->firstOrFail();

            // 1. Transisi proposal ke REJECTED
            $this->proposalWorkflowService->transition(
                proposal: $proposal,
                targetStatus: ProposalStatus::REJECTED,
                actorId: $actor->id,
                reason: $data['reason'],
                notes: $data['notes'] ?? null
            );

            // 2. Update Approval
            $approval->status = ApprovalStatus::REJECTED;
            $approval->completed_at = now();
            $approval->save();

            // 3. Record Action
            ApprovalAction::create([
                'approval_id' => $approval->id,
                'actor_id' => $actor->id,
                'level' => $approval->current_level,
                'action' => ApprovalActionType::REJECT->value,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? 'Proposal ditolak.',
                'acted_at' => now(),
            ]);

            // 4. Create Decision record
            $decisionNumber = $this->numberingService->generateNumber('decision', $proposal->grantProgram);

            $decision = Decision::create([
                'proposal_id' => $proposal->id,
                'approval_id' => $approval->id,
                'issued_by' => $actor->id,
                'decision_number' => $decisionNumber,
                'decision_type' => 'grant_approval',
                'status' => DecisionStatus::PUBLISHED,
                'result' => DecisionResult::REJECTED,
                'decision_date' => now()->toDateString(),
                'approved_amount' => 0,
                'title' => $data['title'] ?? sprintf('Penetapan Penolakan Hibah: %s', $proposal->title),
                'summary' => $data['summary'] ?? sprintf('Proposal %s ditolak pada tahapan persetujuan.', $proposal->proposal_number),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'issued_at' => now(),
            ]);

            // 5. Audit Trail
            $this->auditLogService->record(
                action: 'approval.rejected',
                module: 'approval',
                entityType: Approval::class,
                entityId: $approval->id,
                newValues: [
                    'status' => ApprovalStatus::REJECTED->value,
                    'reason' => $data['reason'],
                    'decision_id' => $decision->id,
                ]
            );

            $this->auditLogService->record(
                action: 'decision.created',
                module: 'decision',
                entityType: Decision::class,
                entityId: $decision->id,
                newValues: [
                    'decision_number' => $decisionNumber,
                    'result' => DecisionResult::REJECTED->value,
                    'reason' => $data['reason'],
                ]
            );

            return $approval->fresh(['proposal', 'recommendation', 'actions.actor', 'decision']);
        });
    }

    public function paginateDecisions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Decision::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,requested_amount,status',
                'proposal.organization:id,name',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'issuer:id,name,email',
                'documents',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        if (! empty($filters['proposal_id'])) {
            $query->where('proposal_id', $filters['proposal_id']);
        }

        if (! empty($filters['grant_program_id'])) {
            $query->whereHas('proposal', function ($q) use ($filters) {
                $q->where('grant_program_id', $filters['grant_program_id']);
            });
        }

        return $query->latest('issued_at')->paginate($perPage);
    }

    public function findDecision(string $id): Decision
    {
        return Decision::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,requested_amount,status',
                'proposal.organization:id,name',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'approval.actions.actor:id,name,email',
                'issuer:id,name,email',
                'documents.versions',
                'documents.template',
                'documents.templateVersion',
            ])
            ->findOrFail($id);
    }
}

