<?php

namespace App\Services;

use App\Enums\QrType;
use App\Enums\ReceiptStatus;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NumberingService $numberingService,
        protected QrService $qrService
    ) {}

    /**
     * Create a new realization receipt.
     */
    public function createReceipt(Proposal $proposal, User $actor, array $data): Receipt
    {
        return DB::transaction(function () use ($proposal, $actor, $data) {
            $receiptNumber = $data['receipt_number'] ?? $this->numberingService->generateNumber(
                'receipt',
                $proposal->grantProgram
            );

            $status = isset($data['status'])
                ? (is_string($data['status']) ? ReceiptStatus::from($data['status']) : $data['status'])
                : ReceiptStatus::ISSUED;

            $payerName = $data['payer_name'] ?? $proposal->organization?->name ?? 'Pemerintah Provinsi';
            $recipientName = $data['recipient_name'] ?? $data['paid_to'] ?? '-';
            $purpose = $data['purpose'] ?? $data['description'] ?? 'Pembayaran Realisasi Kegiatan';

            $receipt = Receipt::create([
                'receipt_number' => $receiptNumber,
                'proposal_id' => $proposal->id,
                'realization_package_id' => $data['realization_package_id'] ?? null,
                'disbursement_id' => $data['disbursement_id'] ?? null,
                'payer_name' => $payerName,
                'recipient_name' => $recipientName,
                'amount' => $data['amount'],
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'purpose' => $purpose,
                'status' => $status,
                'created_by' => $actor->id,
            ]);

            // Generate QR Identity for Receipt
            $this->qrService->generateFor(
                entity: $receipt,
                type: QrType::RECEIPT,
                actor: $actor,
                metadata: [
                    'receipt_number' => $receipt->receipt_number,
                    'amount' => (float) $receipt->amount,
                    'payer_name' => $receipt->payer_name,
                    'recipient_name' => $receipt->recipient_name,
                    'proposal_id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                ]
            );

            $this->auditLogService->record(
                action: 'receipt.created',
                module: 'receipt',
                entityType: Receipt::class,
                entityId: $receipt->id,
                newValues: $receipt->toArray(),
                actorId: $actor->id
            );

            return $receipt->load(['proposal', 'package', 'qrIdentity']);
        });
    }

    /**
     * Update receipt.
     */
    public function updateReceipt(Receipt $receipt, User $actor, array $data): Receipt
    {
        if ($receipt->status === ReceiptStatus::VERIFIED) {
            throw ValidationException::withMessages([
                'receipt' => 'Kuitansi yang telah diverifikasi (VERIFIED) tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($receipt, $actor, $data) {
            $oldValues = $receipt->toArray();

            $receipt->update(array_filter([
                'amount' => $data['amount'] ?? $receipt->amount,
                'receipt_date' => $data['receipt_date'] ?? $receipt->receipt_date,
                'recipient_name' => $data['recipient_name'] ?? $data['paid_to'] ?? $receipt->recipient_name,
                'payer_name' => $data['payer_name'] ?? $receipt->payer_name,
                'purpose' => $data['purpose'] ?? $data['description'] ?? $receipt->purpose,
                'realization_package_id' => $data['realization_package_id'] ?? $receipt->realization_package_id,
            ], fn ($v) => $v !== null));

            $this->auditLogService->record(
                action: 'receipt.updated',
                module: 'receipt',
                entityType: Receipt::class,
                entityId: $receipt->id,
                oldValues: $oldValues,
                newValues: $receipt->toArray(),
                actorId: $actor->id
            );

            return $receipt->fresh(['proposal', 'package', 'qrIdentity']);
        });
    }

    /**
     * Verify receipt.
     */
    public function verifyReceipt(Receipt $receipt, User $actor, ?string $notes = null): Receipt
    {
        if ($receipt->status === ReceiptStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'receipt' => 'Kuitansi yang dibatalkan tidak dapat diverifikasi.',
            ]);
        }

        return DB::transaction(function () use ($receipt, $actor, $notes) {
            $receipt->update([
                'status' => ReceiptStatus::VERIFIED,
            ]);

            $this->auditLogService->record(
                action: 'receipt.verified',
                module: 'receipt',
                entityType: Receipt::class,
                entityId: $receipt->id,
                newValues: [
                    'status' => ReceiptStatus::VERIFIED->value,
                    'notes' => $notes,
                ],
                actorId: $actor->id
            );

            return $receipt->fresh(['proposal', 'qrIdentity']);
        });
    }

    /**
     * Cancel receipt and revoke associated QR.
     */
    public function cancelReceipt(Receipt $receipt, User $actor, string $reason): Receipt
    {
        return DB::transaction(function () use ($receipt, $actor, $reason) {
            $receipt->update([
                'status' => ReceiptStatus::CANCELLED,
            ]);

            if ($receipt->qrIdentity) {
                $this->qrService->revoke(
                    qr: $receipt->qrIdentity,
                    actor: $actor,
                    reason: 'Kuitansi dibatalkan: '.$reason
                );
            }

            $this->auditLogService->record(
                action: 'receipt.cancelled',
                module: 'receipt',
                entityType: Receipt::class,
                entityId: $receipt->id,
                newValues: ['status' => ReceiptStatus::CANCELLED->value, 'reason' => $reason],
                actorId: $actor->id
            );

            return $receipt->fresh(['proposal', 'qrIdentity']);
        });
    }
}
