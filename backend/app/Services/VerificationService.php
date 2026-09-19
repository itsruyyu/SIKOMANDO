<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Enums\VerificationItemResult;
use App\Enums\VerificationResult;
use App\Enums\VerificationStatus;
use App\Models\Proposal;
use App\Models\Verification;
use App\Models\VerificationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function __construct(
        private readonly ProposalWorkflowService $workflowService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(
        Proposal $proposal,
        string $verifierId,
    ): Verification {
        return DB::transaction(function () use ($proposal, $verifierId): Verification {
            $proposal->loadMissing([
                'grantProgram.documentRequirements.requirement',
                'grantProgram.documentRequirements.documentType',
            ]);

            if ($proposal->status === ProposalStatus::SUBMITTED) {
                $proposal = $this->workflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::VERIFICATION,
                    actorId: $verifierId,
                    reason: 'Mulai proses verifikasi administrasi dokumen.',
                );
            } elseif ($proposal->status !== ProposalStatus::VERIFICATION) {
                throw ValidationException::withMessages([
                    'proposal' => [
                        'Proposal harus berada pada status submitted atau verification.',
                    ],
                ]);
            }

            $existing = $proposal->verifications()
                ->where('status', VerificationStatus::IN_PROGRESS->value)
                ->first();

            if ($existing !== null) {
                if ($existing->items()->count() === 0) {
                    $this->populateVerificationItems($existing, $proposal);
                }

                return $existing->load('items');
            }

            $verification = $proposal->verifications()->create([
                'verifier_id' => $verifierId,
                'verification_number' => $this->generateNumber($proposal),
                'status' => VerificationStatus::IN_PROGRESS->value,
                'started_at' => now(),
            ]);

            $this->populateVerificationItems($verification, $proposal);

            $this->auditLogService->record(
                action: 'verification.created',
                module: 'verification',
                entityType: Verification::class,
                entityId: $verification->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'verifier_id' => $verifierId,
                    'verification_number' => $verification->verification_number,
                    'status' => $verification->status,
                ],
            );

            return $verification->load('items');
        });
    }

    public function updateItem(
        Verification $verification,
        VerificationItem $item,
        string $result,
        ?string $notes = null,
    ): VerificationItem {
        return DB::transaction(function () use (
            $verification,
            $item,
            $result,
            $notes
        ): VerificationItem {
            $statusValue = $verification->status instanceof \BackedEnum
                ? $verification->status->value
                : (string) $verification->status;

            if ($statusValue !== VerificationStatus::IN_PROGRESS->value) {
                throw ValidationException::withMessages([
                    'verification' => [
                        'Verifikasi sudah tidak dapat diubah.',
                    ],
                ]);
            }

            if ($item->verification_id !== $verification->id) {
                throw ValidationException::withMessages([
                    'item' => [
                        'Item tidak termasuk dalam verifikasi ini.',
                    ],
                ]);
            }

            $oldValues = $item->only([
                'result',
                'notes',
                'checked_by',
                'checked_at',
            ]);

            $item->update([
                'result' => $result,
                'notes' => $notes,
                'checked_by' => auth()->id(),
                'checked_at' => now(),
            ]);

            $this->auditLogService->record(
                action: 'verification.item_updated',
                module: 'verification',
                entityType: VerificationItem::class,
                entityId: $item->id,
                oldValues: $oldValues,
                newValues: $item->only([
                    'result',
                    'notes',
                    'checked_by',
                    'checked_at',
                ]),
                metadata: [
                    'verification_id' => $verification->id,
                ],
            );

            return $item->refresh();
        });
    }

    public function complete(Verification $verification): Verification
    {
        return DB::transaction(function () use ($verification): Verification {
            $verification->loadMissing(['items', 'proposal.grantProgram']);

            if ($verification->items->isEmpty()) {
                $this->populateVerificationItems($verification, $verification->proposal);
                $verification->load('items');
            }

            $statusValue = $verification->status instanceof \BackedEnum
                ? $verification->status->value
                : (string) $verification->status;

            if ($statusValue !== VerificationStatus::IN_PROGRESS->value) {
                throw ValidationException::withMessages([
                    'verification' => [
                        'Verifikasi sudah selesai atau tidak aktif.',
                    ],
                ]);
            }

            $pendingCount = $verification->items
                ->filter(fn (VerificationItem $item): bool => $item->result === VerificationItemResult::PENDING
                    || ($item->result?->value ?? $item->result) === VerificationItemResult::PENDING->value
                )
                ->count();

            if ($verification->items->isEmpty() || $pendingCount > 0) {
                throw ValidationException::withMessages([
                    'items' => [
                        'Seluruh item verifikasi harus diperiksa terlebih dahulu.',
                    ],
                ]);
            }

            $hasFailure = $verification->items->contains(
                fn (VerificationItem $item): bool => in_array(
                    $item->result,
                    [
                        VerificationItemResult::FAIL->value,
                        VerificationItemResult::NEED_REVISION->value,
                    ],
                    true
                )
            );

            $result = $hasFailure
                ? VerificationResult::NEED_REVISION->value
                : VerificationResult::PASS->value;

            $status = $hasFailure
                ? VerificationStatus::REVISION_REQUIRED->value
                : VerificationStatus::COMPLETED->value;

            $verification->update([
                'status' => $status,
                'result' => $result,
                'completed_at' => now(),
                'summary' => $hasFailure
                    ? 'Verifikasi memerlukan perbaikan.'
                    : 'Verifikasi dinyatakan lulus.',
            ]);

            $nextStatus = $hasFailure
                ? ProposalStatus::REVISION
                : ProposalStatus::VERIFIED;

            $this->workflowService->transition(
                proposal: $verification->proposal()->lockForUpdate()->firstOrFail(),
                targetStatus: $nextStatus,
                actorId: (string) auth()->id(),
                reason: $hasFailure
                    ? 'Hasil verifikasi memerlukan perbaikan.'
                    : 'Hasil verifikasi dinyatakan lulus.',
                notes: sprintf(
                    'Verification ID: %s; Result: %s',
                    $verification->id,
                    $result
                ),
            );

            $this->auditLogService->record(
                action: 'verification.completed',
                module: 'verification',
                entityType: Verification::class,
                entityId: $verification->id,
                newValues: [
                    'status' => $status,
                    'result' => $result,
                    'completed_at' => $verification->completed_at,
                ],
            );

            return $verification->refresh()->load('items');
        });
    }

    public function populateVerificationItems(Verification $verification, Proposal $proposal): void
    {
        $requirements = $proposal->grantProgram
            ?->documentRequirements()
            ->with(['requirement', 'documentType'])
            ->where(function ($q) {
                $q->where('scope', 'proposal')
                    ->orWhereNull('scope');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($requirements && $requirements->isNotEmpty()) {
            foreach ($requirements as $documentRequirement) {
                $requirementName = $documentRequirement->requirement?->name
                    ?? $documentRequirement->documentType?->name
                    ?? 'Persyaratan dokumen';

                $requirementCode = $documentRequirement->requirement?->code
                    ?? $documentRequirement->documentType?->code;

                $verification->items()->create([
                    'requirement_id' => $documentRequirement->requirement_id,
                    'document_type_id' => $documentRequirement->document_type_id,
                    'item_code' => $requirementCode,
                    'item_name' => $requirementName,
                    'result' => VerificationItemResult::PENDING->value,
                ]);
            }
        } else {
            $systemReqs = \App\Models\Requirement::where('is_active', true)->get();
            if ($systemReqs->isNotEmpty()) {
                foreach ($systemReqs as $sr) {
                    $verification->items()->create([
                        'requirement_id' => $sr->id,
                        'document_type_id' => null,
                        'item_code' => $sr->code,
                        'item_name' => $sr->name,
                        'result' => VerificationItemResult::PENDING->value,
                    ]);
                }
            } else {
                $defaults = [
                    ['code' => 'REQ-ADM-01', 'name' => 'Surat Permohonan Hibah kepada Gubernur c.q. Kepala Biro Kesra'],
                    ['code' => 'REQ-ADM-02', 'name' => 'Akta Notaris & Pengesahan Kemenkumham'],
                    ['code' => 'REQ-ADM-03', 'name' => 'Nomor Pokok Wajib Pajak (NPWP) Lembaga Pemohon'],
                    ['code' => 'REQ-ADM-04', 'name' => 'Surat Keterangan Domisili Sekretariat Resmi'],
                    ['code' => 'REQ-ADM-05', 'name' => 'Rincian Anggaran Biaya (RAB) Terperinci & Wajar'],
                    ['code' => 'REQ-ADM-06', 'name' => 'Surat Pernyataan Tanggung Jawab Mutlak (SPTJM) bermaterai'],
                ];
                foreach ($defaults as $def) {
                    $verification->items()->create([
                        'item_code' => $def['code'],
                        'item_name' => $def['name'],
                        'result' => VerificationItemResult::PENDING->value,
                    ]);
                }
            }
        }
    }

    private function generateNumber(Proposal $proposal): string
    {
        $prefix = 'VER-'.now()->format('Ym').'-';

        $sequence = Verification::query()
            ->where('verification_number', 'like', $prefix.'%')
            ->count() + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
