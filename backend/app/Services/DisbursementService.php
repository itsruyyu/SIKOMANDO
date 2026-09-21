<?php

namespace App\Services;

use App\Enums\DecisionDocumentStatus;
use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Enums\DisbursementPlanStatus;
use App\Enums\DisbursementStatus;
use App\Enums\DisbursementTransactionStatus;
use App\Enums\ProposalStatus;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\DisbursementTransaction;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisbursementService
{
    public function __construct(
        protected ProposalWorkflowService $proposalWorkflowService,
        protected NumberingService $numberingService,
        protected AuditLogService $auditLogService
    ) {}

    public function validateDisbursementPrerequisites(Proposal $proposal): Decision
    {
        $currentStatus = $proposal->status instanceof ProposalStatus
            ? $proposal->status
            : ProposalStatus::from($proposal->status);

        if (! in_array($currentStatus, [ProposalStatus::APPROVED, ProposalStatus::DISBURSED], true)) {
            throw ValidationException::withMessages([
                'proposal' => 'Pencairan hanya dapat diproses untuk proposal yang telah disetujui (APPROVED).',
            ]);
        }

        // Check official approved decision
        $decision = $proposal->decisions()
            ->where('result', DecisionResult::APPROVED)
            ->where('status', DecisionStatus::PUBLISHED)
            ->latest('issued_at')
            ->first();

        if ($decision === null) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal belum memiliki surat ketetapan keputusan resmi yang disetujui (APPROVED).',
            ]);
        }

        // Check active official SK document
        $hasActiveSk = $decision->documents()
            ->where('document_type', 'decision_letter')
            ->where('status', DecisionDocumentStatus::ACTIVE)
            ->exists();

        if (! $hasActiveSk) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal belum memiliki dokumen Surat Keputusan (SK) resmi yang sah dan aktif.',
            ]);
        }

        return $decision;
    }

    public function createPlan(Proposal $proposal, User $actor, array $data): DisbursementPlan
    {
        $decision = $this->validateDisbursementPrerequisites($proposal);

        $approvedCeiling = (float) ($decision->approved_amount ?: $proposal->approved_amount);

        // Stages preparation
        $stages = $data['stages'] ?? [];

        if (empty($stages)) {
            // Default to single stage with full or specified planned amount
            $plannedAmount = isset($data['planned_amount'])
                ? (float) $data['planned_amount']
                : $approvedCeiling;

            $stages = [
                [
                    'stage_number' => 1,
                    'planned_amount' => $plannedAmount,
                    'planned_date' => $data['planned_date'] ?? now()->toDateString(),
                    'bank_name' => $data['bank_name'] ?? null,
                    'bank_account_number' => $data['bank_account_number'] ?? null,
                    'bank_account_name' => $data['bank_account_name'] ?? null,
                    'notes' => $data['notes'] ?? 'Pencairan tahap tunggal.',
                ],
            ];
        }

        // Calculate total planned amount
        $totalPlanned = 0;
        foreach ($stages as $stage) {
            $amount = (float) ($stage['planned_amount'] ?? 0);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'planned_amount' => 'Nominal rencana pencairan setiap tahap harus lebih besar dari 0.',
                ]);
            }
            $totalPlanned += $amount;
        }

        if ($totalPlanned > $approvedCeiling) {
            throw ValidationException::withMessages([
                'planned_amount' => sprintf(
                    'Total rencana pencairan (Rp %s) melebihi plafon nominal yang disetujui (Rp %s).',
                    number_format($totalPlanned, 0, ',', '.'),
                    number_format($approvedCeiling, 0, ',', '.')
                ),
            ]);
        }

        return DB::transaction(function () use ($proposal, $actor, $data, $stages, $totalPlanned) {
            $planNumber = $this->numberingService->generateNumber('disbursement_plan', $proposal->grantProgram);

            $plan = DisbursementPlan::create([
                'proposal_id' => $proposal->id,
                'created_by' => $actor->id,
                'plan_number' => $planNumber,
                'total_stages' => count($stages),
                'planned_amount' => $totalPlanned,
                'status' => DisbursementPlanStatus::SUBMITTED,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            foreach ($stages as $index => $stage) {
                $stageNum = $stage['stage_number'] ?? ($index + 1);
                $disbursementNumber = $this->numberingService->generateNumber('disbursement', $proposal->grantProgram);

                Disbursement::create([
                    'disbursement_plan_id' => $plan->id,
                    'proposal_id' => $proposal->id,
                    'stage_number' => $stageNum,
                    'disbursement_number' => $disbursementNumber,
                    'planned_amount' => (float) $stage['planned_amount'],
                    'approved_amount' => (float) $stage['planned_amount'],
                    'paid_amount' => 0,
                    'status' => DisbursementStatus::PENDING,
                    'planned_date' => $stage['planned_date'] ?? now()->toDateString(),
                    'bank_name' => $stage['bank_name'] ?? $data['bank_name'] ?? null,
                    'bank_account_number' => $stage['bank_account_number'] ?? $data['bank_account_number'] ?? null,
                    'bank_account_name' => $stage['bank_account_name'] ?? $data['bank_account_name'] ?? null,
                    'notes' => $stage['notes'] ?? null,
                ]);
            }

            $this->auditLogService->record(
                action: 'disbursement_plan.created',
                module: 'disbursement',
                entityType: DisbursementPlan::class,
                entityId: $plan->id,
                newValues: [
                    'plan_number' => $planNumber,
                    'proposal_id' => $proposal->id,
                    'planned_amount' => $totalPlanned,
                    'total_stages' => count($stages),
                ]
            );

            return $plan->fresh(['disbursements', 'proposal.organization', 'creator:id,name,email']);
        });
    }

    public function verifyDisbursement(Disbursement $disbursement, User $actor, array $data = []): Disbursement
    {
        if ($disbursement->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Pencairan sudah berstatus final dan tidak dapat diverifikasi kembali.',
            ]);
        }

        return DB::transaction(function () use ($disbursement, $actor, $data) {
            $disbursement->status = DisbursementStatus::VERIFIED;

            if (! empty($data['bank_name'])) {
                $disbursement->bank_name = $data['bank_name'];
            }
            if (! empty($data['bank_account_number'])) {
                $disbursement->bank_account_number = $data['bank_account_number'];
            }
            if (! empty($data['bank_account_name'])) {
                $disbursement->bank_account_name = $data['bank_account_name'];
            }
            if (! empty($data['notes'])) {
                $disbursement->notes = $data['notes'];
            }

            $disbursement->save();

            $this->auditLogService->record(
                action: 'disbursement.verified',
                module: 'disbursement',
                entityType: Disbursement::class,
                entityId: $disbursement->id,
                newValues: [
                    'status' => DisbursementStatus::VERIFIED->value,
                    'verified_by' => $actor->id,
                ]
            );

            return $disbursement->fresh(['proposal.organization', 'plan', 'transactions']);
        });
    }

    public function approveDisbursement(Disbursement $disbursement, User $actor, array $data = []): Disbursement
    {
        if ($disbursement->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Pencairan sudah berstatus final dan tidak dapat disetujui kembali.',
            ]);
        }

        // Maker-Checker / Four-Eyes check: Plan creator cannot approve the disbursement
        $planCreatorId = $disbursement->plan?->created_by;
        if ($planCreatorId && $planCreatorId === $actor->id) {
            throw ValidationException::withMessages([
                'actor' => 'Maker tidak dapat melakukan approval atas pengajuan pencairan sendiri (Maker-Checker violation).',
            ]);
        }

        return DB::transaction(function () use ($disbursement, $actor, $data) {
            $approvedAmount = isset($data['approved_amount'])
                ? (float) $data['approved_amount']
                : (float) $disbursement->planned_amount;

            $disbursement->status = DisbursementStatus::APPROVED;
            $disbursement->approved_amount = $approvedAmount;
            $disbursement->approved_date = now()->toDateString();

            if (! empty($data['notes'])) {
                $disbursement->notes = $data['notes'];
            }

            $disbursement->save();

            $this->auditLogService->record(
                action: 'disbursement.approved',
                module: 'disbursement',
                entityType: Disbursement::class,
                entityId: $disbursement->id,
                newValues: [
                    'status' => DisbursementStatus::APPROVED->value,
                    'approved_amount' => $approvedAmount,
                    'approved_by' => $actor->id,
                ]
            );

            return $disbursement->fresh(['proposal.organization', 'plan', 'transactions']);
        });
    }

    public function recordTransaction(Disbursement $disbursement, User $actor, array $data): DisbursementTransaction
    {
        return DB::transaction(function () use ($disbursement, $actor, $data) {
            // 1. Check idempotency key if supplied
            if (! empty($data['idempotency_key'])) {
                $existingTx = DisbursementTransaction::query()
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();

                if ($existingTx !== null) {
                    return $existingTx->fresh(['disbursement', 'recorder:id,name,email']);
                }
            }

            // 2. Lock disbursement row
            $lockedDisbursement = Disbursement::query()
                ->whereKey($disbursement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedDisbursement->status, [DisbursementStatus::APPROVED, DisbursementStatus::PROCESSED], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi pencairan hanya dapat dicatat setelah pencairan disetujui (APPROVED).',
                ]);
            }

            // 3. Prevent duplicate bank reference
            if (! empty($data['bank_reference'])) {
                $refExists = DisbursementTransaction::query()
                    ->where('bank_reference', $data['bank_reference'])
                    ->exists();

                if ($refExists) {
                    throw ValidationException::withMessages([
                        'bank_reference' => 'Referensi transaksi bank sudah digunakan pada pencairan lain.',
                    ]);
                }
            }

            // 4. Lock proposal row and validate ceiling limits
            $proposal = Proposal::query()
                ->whereKey($lockedDisbursement->proposal_id)
                ->with(['grantProgram', 'organization'])
                ->lockForUpdate()
                ->firstOrFail();

            $decision = $proposal->decisions()
                ->where('result', DecisionResult::APPROVED)
                ->latest('issued_at')
                ->firstOrFail();

            $approvedCeiling = (float) ($decision->approved_amount ?: $proposal->approved_amount);

            $amount = isset($data['amount'])
                ? (float) $data['amount']
                : (float) ($lockedDisbursement->approved_amount ?: $lockedDisbursement->planned_amount);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pencairan harus lebih besar dari 0.',
                ]);
            }

            if ($amount > (float) ($lockedDisbursement->approved_amount ?: $lockedDisbursement->planned_amount)) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pencairan melebihi nominal tahap yang telah disetujui.',
                ]);
            }

            $currentPaidTotal = (float) Disbursement::query()
                ->where('proposal_id', $proposal->id)
                ->where('status', DisbursementStatus::PAID)
                ->where('id', '!=', $lockedDisbursement->id)
                ->sum('paid_amount');

            if (($currentPaidTotal + $amount) > $approvedCeiling) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Total pencairan kumulatif melebihi nominal plafon yang disetujui (Rp %s).',
                        number_format($approvedCeiling, 0, ',', '.')
                    ),
                ]);
            }

            $transactionNumber = $this->numberingService->generateNumber('disbursement_transaction', $proposal->grantProgram);

            $transaction = DisbursementTransaction::create([
                'disbursement_id' => $lockedDisbursement->id,
                'recorded_by' => $actor->id,
                'transaction_number' => $transactionNumber,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'transaction_type' => $data['transaction_type'] ?? 'transfer',
                'amount' => $amount,
                'status' => DisbursementTransactionStatus::CONFIRMED,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'bank_reference' => $data['bank_reference'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? $lockedDisbursement->bank_account_name ?? $proposal->organization?->name,
                'bank_name' => $data['bank_name'] ?? $lockedDisbursement->bank_name,
                'bank_account_number' => $data['bank_account_number'] ?? $lockedDisbursement->bank_account_number,
                'notes' => $data['notes'] ?? 'Pencairan dana telah ditransfer.',
            ]);

            // Update Disbursement to PAID
            $lockedDisbursement->paid_amount = $amount;
            $lockedDisbursement->status = DisbursementStatus::PAID;
            $lockedDisbursement->paid_date = now()->toDateString();
            $lockedDisbursement->save();

            // Transition Proposal status to DISBURSED if currently APPROVED
            $propStatus = $proposal->status instanceof ProposalStatus
                ? $proposal->status
                : ProposalStatus::tryFrom((string) $proposal->status);

            if ($propStatus === ProposalStatus::APPROVED) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::DISBURSED,
                    actorId: $actor->id,
                    reason: 'Dana hibah telah berhasil dicairkan kepada penerima.',
                    notes: $data['notes'] ?? null
                );
            }

            // Check if all disbursements in plan are paid
            if ($lockedDisbursement->plan) {
                $unpaidCount = $lockedDisbursement->plan->disbursements()
                    ->where('status', '!=', DisbursementStatus::PAID)
                    ->count();

                if ($unpaidCount === 0) {
                    $lockedDisbursement->plan->update(['status' => DisbursementPlanStatus::COMPLETED]);

                    // BE-14: Otomatisasi transisi ke IMPLEMENTATION setelah seluruh pencairan tuntas
                    $freshProposal = $proposal->fresh();
                    $freshStatus = $freshProposal->status instanceof ProposalStatus
                        ? $freshProposal->status
                        : ProposalStatus::tryFrom((string) $freshProposal->status);

                    if ($freshStatus === ProposalStatus::DISBURSED) {
                        $this->proposalWorkflowService->transition(
                            proposal: $freshProposal,
                            targetStatus: ProposalStatus::IMPLEMENTATION,
                            actorId: $actor->id,
                            reason: 'Seluruh tahap pencairan dana telah selesai disalurkan. Usulan memasuki tahap pelaksanaan program.',
                            notes: 'Transisi otomatis sistem setelah seluruh tahap pencairan berstatus PAID.'
                        );
                    }
                }
            }

            // Audit Trail
            $this->auditLogService->record(
                action: 'disbursement.paid',
                module: 'disbursement',
                entityType: Disbursement::class,
                entityId: $lockedDisbursement->id,
                newValues: [
                    'status' => DisbursementStatus::PAID->value,
                    'paid_amount' => $amount,
                    'paid_date' => $lockedDisbursement->paid_date,
                    'transaction_number' => $transactionNumber,
                ]
            );

            $this->auditLogService->record(
                action: 'disbursement.transaction_recorded',
                module: 'disbursement',
                entityType: DisbursementTransaction::class,
                entityId: $transaction->id,
                newValues: [
                    'transaction_number' => $transactionNumber,
                    'amount' => $amount,
                    'bank_reference' => $data['bank_reference'] ?? null,
                    'idempotency_key' => $data['idempotency_key'] ?? null,
                ]
            );

            return $transaction->fresh(['disbursement', 'recorder:id,name,email']);
        });
    }

    public function getDisbursementSummary(Proposal $proposal): array
    {
        $decision = $proposal->decisions()->where('result', DecisionResult::APPROVED)->latest('issued_at')->first();
        $approvedCeiling = (float) ($decision?->approved_amount ?: $proposal->approved_amount ?: $proposal->requested_amount);

        $totalPaid = (float) Disbursement::query()
            ->where('proposal_id', $proposal->id)
            ->where('status', DisbursementStatus::PAID)
            ->sum('paid_amount');

        $remaining = max(0.0, $approvedCeiling - $totalPaid);

        $disbursements = Disbursement::query()
            ->where('proposal_id', $proposal->id)
            ->orderBy('stage_number')
            ->get();

        return [
            'proposal_id' => $proposal->id,
            'proposal_number' => $proposal->proposal_number,
            'proposal_title' => $proposal->title,
            'approved_ceiling' => $approvedCeiling,
            'total_disbursed' => $totalPaid,
            'remaining_amount' => $remaining,
            'disbursement_progress_percentage' => $approvedCeiling > 0 ? round(($totalPaid / $approvedCeiling) * 100, 2) : 0,
            'total_stages' => $disbursements->count(),
            'stages' => $disbursements->map(fn (Disbursement $d) => [
                'id' => $d->id,
                'stage_number' => $d->stage_number,
                'disbursement_number' => $d->disbursement_number,
                'planned_amount' => (float) $d->planned_amount,
                'approved_amount' => (float) $d->approved_amount,
                'paid_amount' => (float) $d->paid_amount,
                'status' => $d->status?->value,
                'status_label' => $d->status?->label(),
                'paid_date' => $d->paid_date?->toDateString(),
            ])->all(),
        ];
    }

    public function paginateDisbursements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Disbursement::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,status,requested_amount,approved_amount',
                'proposal.organization:id,name',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'plan:id,plan_number,total_stages,status',
                'transactions',
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

    public function findDisbursement(string $id): Disbursement
    {
        return Disbursement::query()
            ->with([
                'proposal:id,proposal_number,title,applicant_id,organization_id,grant_program_id,status,requested_amount,approved_amount',
                'proposal.organization:id,name,phone,email,address',
                'proposal.grantProgram:id,code,name,fiscal_year',
                'plan.creator:id,name,email',
                'transactions.recorder:id,name,email',
            ])
            ->findOrFail($id);
    }
}
