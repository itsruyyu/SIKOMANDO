<?php

namespace App\Services;

use App\Enums\HandoverStatus;
use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Models\Handover;
use App\Models\HandoverItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandoverService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NumberingService $numberingService,
        protected QrService $qrService
    ) {}

    /**
     * Create a new BAST (Handover) record.
     */
    public function createHandover(RealizationPackage $package, User $actor, array $data, array $items = []): Handover
    {
        return DB::transaction(function () use ($package, $actor, $data, $items) {
            $handoverNumber = $data['handover_number'] ?? $this->numberingService->generateNumber(
                'handover',
                $package->proposal?->grantProgram
            );

            $giverName = $data['giver_name'] ?? $data['handover_by_name'] ?? '-';
            $giverPosition = $data['giver_position'] ?? $data['handover_by_position'] ?? null;
            $recipientName = $data['recipient_name'] ?? $data['handover_to_name'] ?? '-';
            $recipientPosition = $data['recipient_position'] ?? $data['handover_to_position'] ?? null;

            $handover = Handover::create([
                'handover_number' => $handoverNumber,
                'realization_package_id' => $package->id,
                'proposal_id' => $package->proposal_id,
                'handover_date' => $data['handover_date'] ?? now()->toDateString(),
                'giver_name' => $giverName,
                'giver_position' => $giverPosition,
                'recipient_name' => $recipientName,
                'recipient_position' => $recipientPosition,
                'status' => HandoverStatus::DRAFT,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            // Attach items if passed, or default to all items in the package
            $itemsToAttach = ! empty($items) ? $items : $package->items->map(fn ($itm) => [
                'realization_item_id' => $itm->id,
                'notes' => $itm->notes,
            ])->toArray();

            foreach ($itemsToAttach as $itemData) {
                HandoverItem::create([
                    'handover_id' => $handover->id,
                    'realization_item_id' => $itemData['realization_item_id'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Generate QR Identity for Handover
            $this->qrService->generateFor(
                entity: $handover,
                type: QrType::HANDOVER,
                actor: $actor,
                metadata: [
                    'handover_number' => $handover->handover_number,
                    'package_number' => $package->package_number,
                    'handover_to' => $handover->recipient_name,
                    'items_count' => count($itemsToAttach),
                ]
            );

            $this->auditLogService->record(
                action: 'handover.created',
                module: 'handover',
                entityType: Handover::class,
                entityId: $handover->id,
                newValues: $handover->toArray(),
                actorId: $actor->id
            );

            return $handover->load(['package', 'proposal', 'items', 'qrIdentity']);
        });
    }

    /**
     * Submit handover.
     */
    public function submitHandover(Handover $handover, User $actor): Handover
    {
        if ($handover->status !== HandoverStatus::DRAFT) {
            throw ValidationException::withMessages([
                'handover' => 'Hanya BAST berstatus DRAFT yang dapat diajukan.',
            ]);
        }

        return DB::transaction(function () use ($handover, $actor) {
            $handover->update([
                'status' => HandoverStatus::SUBMITTED,
            ]);

            $this->auditLogService->record(
                action: 'handover.submitted',
                module: 'handover',
                entityType: Handover::class,
                entityId: $handover->id,
                newValues: ['status' => HandoverStatus::SUBMITTED->value],
                actorId: $actor->id
            );

            return $handover->fresh(['items', 'qrIdentity']);
        });
    }

    /**
     * Complete handover and update item statuses.
     */
    public function completeHandover(Handover $handover, User $actor): Handover
    {
        return DB::transaction(function () use ($handover, $actor) {
            $handover->update([
                'status' => HandoverStatus::COMPLETED,
            ]);

            // Update item statuses to ASSIGNED
            foreach ($handover->items as $item) {
                $item->update([
                    'status' => RealizationItemStatus::ASSIGNED,
                ]);
            }

            $this->auditLogService->record(
                action: 'handover.completed',
                module: 'handover',
                entityType: Handover::class,
                entityId: $handover->id,
                newValues: ['status' => HandoverStatus::COMPLETED->value],
                actorId: $actor->id
            );

            return $handover->fresh(['items', 'qrIdentity']);
        });
    }

    /**
     * Cancel handover and revoke QR.
     */
    public function cancelHandover(Handover $handover, User $actor, string $reason): Handover
    {
        return DB::transaction(function () use ($handover, $actor, $reason) {
            $handover->update([
                'status' => HandoverStatus::CANCELLED,
                'notes' => ($handover->notes ? $handover->notes."\n" : '').'Dibatalkan: '.$reason,
            ]);

            if ($handover->qrIdentity) {
                $this->qrService->revoke(
                    qr: $handover->qrIdentity,
                    actor: $actor,
                    reason: 'BAST dibatalkan: '.$reason
                );
            }

            $this->auditLogService->record(
                action: 'handover.cancelled',
                module: 'handover',
                entityType: Handover::class,
                entityId: $handover->id,
                newValues: ['status' => HandoverStatus::CANCELLED->value, 'reason' => $reason],
                actorId: $actor->id
            );

            return $handover->fresh(['qrIdentity']);
        });
    }
}
