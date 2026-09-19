<?php

namespace App\Services;

use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Enums\RealizationPackageStatus;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationItemHistory;
use App\Models\RealizationPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RealizationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NumberingService $numberingService,
        protected QrService $qrService
    ) {}

    /**
     * Create a realization package for a proposal.
     */
    public function createPackage(Proposal $proposal, User $actor, array $data): RealizationPackage
    {
        return DB::transaction(function () use ($proposal, $actor, $data) {
            $packageNumber = $data['package_number'] ?? $this->numberingService->generateNumber(
                'realization_package',
                $proposal->grantProgram
            );

            $package = RealizationPackage::create([
                'proposal_id' => $proposal->id,
                'package_number' => $packageNumber,
                'name' => $data['package_name'] ?? $data['name'] ?? 'Paket Realisasi',
                'package_name' => $data['package_name'] ?? $data['name'] ?? 'Paket Realisasi',
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'] ?? 0,
                'status' => RealizationPackageStatus::DRAFT,
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'created_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Generate QR identity for package
            $this->qrService->generateFor(
                entity: $package,
                type: QrType::REALIZATION_PACKAGE,
                actor: $actor,
                metadata: [
                    'package_number' => $package->package_number,
                    'package_name' => $package->package_name,
                    'proposal_id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                ]
            );

            $this->auditLogService->record(
                action: 'realization_package.created',
                module: 'realization',
                entityType: RealizationPackage::class,
                entityId: $package->id,
                newValues: $package->toArray(),
                actorId: $actor->id
            );

            return $package->load(['proposal', 'qrIdentity']);
        });
    }

    /**
     * Update an existing package.
     */
    public function updatePackage(RealizationPackage $package, User $actor, array $data): RealizationPackage
    {
        if ($package->status === RealizationPackageStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'package' => 'Paket realisasi yang sudah selesai (COMPLETED) tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($package, $actor, $data) {
            $oldValues = $package->toArray();
            $package->update(array_filter([
                'package_name' => $data['package_name'] ?? $package->package_name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $package->description,
                'target_completion_date' => $data['target_completion_date'] ?? $package->target_completion_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $package->notes,
            ], fn ($v) => $v !== null));

            $this->auditLogService->record(
                action: 'realization_package.updated',
                module: 'realization',
                entityType: RealizationPackage::class,
                entityId: $package->id,
                oldValues: $oldValues,
                newValues: $package->toArray(),
                actorId: $actor->id
            );

            return $package->fresh(['proposal', 'qrIdentity', 'items']);
        });
    }

    /**
     * Submit realization package for verification.
     */
    public function submitPackage(RealizationPackage $package, User $actor): RealizationPackage
    {
        if ($package->status !== RealizationPackageStatus::DRAFT) {
            throw ValidationException::withMessages([
                'package' => 'Hanya paket berstatus DRAFT yang dapat diajukan verifikasi.',
            ]);
        }

        if ($package->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Paket realisasi harus memiliki minimal satu barang/item realisasi.',
            ]);
        }

        return DB::transaction(function () use ($package, $actor) {
            $package->update([
                'status' => RealizationPackageStatus::SUBMITTED,
            ]);

            $this->auditLogService->record(
                action: 'realization_package.submitted',
                module: 'realization',
                entityType: RealizationPackage::class,
                entityId: $package->id,
                newValues: ['status' => RealizationPackageStatus::SUBMITTED->value],
                actorId: $actor->id
            );

            return $package->fresh(['items', 'proposal']);
        });
    }

    /**
     * Verify realization package.
     */
    public function verifyPackage(RealizationPackage $package, User $actor, array $data): RealizationPackage
    {
        if ($package->status !== RealizationPackageStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'package' => 'Hanya paket yang sudah diajukan (SUBMITTED) yang dapat diverifikasi.',
            ]);
        }

        return DB::transaction(function () use ($package, $actor, $data) {
            $status = ($data['status'] ?? 'VERIFIED') === 'COMPLETED'
                ? RealizationPackageStatus::COMPLETED
                : RealizationPackageStatus::VERIFIED;

            $package->update([
                'status' => $status,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'actual_completion_date' => $status === RealizationPackageStatus::COMPLETED ? now()->toDateString() : null,
                'notes' => $data['notes'] ?? $package->notes,
            ]);

            $this->auditLogService->record(
                action: 'realization_package.verified',
                module: 'realization',
                entityType: RealizationPackage::class,
                entityId: $package->id,
                newValues: [
                    'status' => $status->value,
                    'verified_by' => $actor->id,
                    'verified_at' => now()->toIso8601String(),
                ],
                actorId: $actor->id
            );

            return $package->fresh(['items', 'proposal', 'qrIdentity']);
        });
    }

    /**
     * Add realization item to package, linked to budget item (RAB) if provided.
     */
    public function addItem(RealizationPackage $package, User $actor, array $data): RealizationItem
    {
        return DB::transaction(function () use ($package, $actor, $data) {
            // Validate budget item belongs to proposal if provided
            if (! empty($data['proposal_budget_item_id'])) {
                $budgetItem = ProposalBudgetItem::where('id', $data['proposal_budget_item_id'])
                    ->where('proposal_id', $package->proposal_id)
                    ->first();

                if (! $budgetItem) {
                    throw ValidationException::withMessages([
                        'proposal_budget_item_id' => 'Item anggaran (RAB) tidak valid atau tidak sesuai dengan proposal.',
                    ]);
                }
            }

            $count = $package->items()->count() + 1;
            $itemCode = $data['item_code'] ?? sprintf('ITM-%s-%03d', Str::slug($package->package_number), $count);

            $quantity = $data['quantity'] ?? 1;
            $unitPrice = $data['unit_price'] ?? 0;
            $totalPrice = $data['total_price'] ?? ($quantity * $unitPrice);

            $status = isset($data['status'])
                ? (is_string($data['status']) ? (RealizationItemStatus::tryFrom(strtolower($data['status'])) ?? RealizationItemStatus::from($data['status'])) : $data['status'])
                : RealizationItemStatus::CREATED;

            $condition = isset($data['condition'])
                ? (is_string($data['condition']) ? (RealizationItemCondition::tryFrom(strtolower($data['condition'])) ?? RealizationItemCondition::from($data['condition'])) : $data['condition'])
                : RealizationItemCondition::GOOD;

            $item = RealizationItem::create([
                'realization_package_id' => $package->id,
                'proposal_budget_item_id' => $data['proposal_budget_item_id'] ?? null,
                'item_code' => $itemCode,
                'name' => $data['item_name'] ?? $data['name'] ?? 'Item Realisasi',
                'item_name' => $data['item_name'] ?? $data['name'] ?? 'Item Realisasi',
                'specification' => $data['specification'] ?? null,
                'quantity' => $quantity,
                'unit' => $data['unit'] ?? 'unit',
                'unit_price' => $unitPrice,
                'total_amount' => $totalPrice,
                'total_price' => $totalPrice,
                'status' => $status,
                'condition' => $condition,
                'serial_number' => $data['serial_number'] ?? null,
                'brand' => $data['brand'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'location_name' => $data['location_address'] ?? null,
                'location_address' => $data['location_address'] ?? null,
                'location_notes' => $data['location_notes'] ?? null,
                'photos' => $data['photos'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            // Create initial history record
            RealizationItemHistory::create([
                'realization_item_id' => $item->id,
                'action' => 'CREATED',
                'status' => $item->status->value,
                'actor_id' => $actor->id,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'notes' => 'Item realisasi didaftarkan ke sistem.',
                'metadata' => [
                    'previous_status' => null,
                    'new_status' => $item->status->value,
                    'condition' => $item->condition->value,
                    'location_address' => $item->location_address,
                ],
            ]);

            // Generate Item QR Identity
            $this->qrService->generateFor(
                entity: $item,
                type: QrType::REALIZATION_ITEM,
                actor: $actor,
                metadata: [
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'package_number' => $package->package_number,
                    'serial_number' => $item->serial_number,
                ]
            );

            // Update package total amount
            $package->update([
                'total_amount' => $package->items()->sum('total_amount'),
            ]);

            $this->auditLogService->record(
                action: 'realization_item.created',
                module: 'realization',
                entityType: RealizationItem::class,
                entityId: $item->id,
                newValues: $item->toArray(),
                actorId: $actor->id
            );

            return $item->load(['package', 'budgetItem', 'qrIdentity']);
        });
    }

    /**
     * Update realization item attributes, status, condition, or physical geo-location.
     */
    public function updateItem(RealizationItem $item, User $actor, array $data): RealizationItem
    {
        return DB::transaction(function () use ($item, $actor, $data) {
            $prevStatus = $item->status;
            $prevCondition = $item->condition;
            $oldValues = $item->toArray();

            $newStatus = isset($data['status'])
                ? (is_string($data['status']) ? (RealizationItemStatus::tryFrom(strtolower($data['status'])) ?? RealizationItemStatus::from($data['status'])) : $data['status'])
                : $prevStatus;

            $newCondition = isset($data['condition'])
                ? (is_string($data['condition']) ? (RealizationItemCondition::tryFrom(strtolower($data['condition'])) ?? RealizationItemCondition::from($data['condition'])) : $data['condition'])
                : $prevCondition;

            $quantity = $data['quantity'] ?? $item->quantity;
            $unitPrice = $data['unit_price'] ?? $item->unit_price;
            $totalPrice = $data['total_price'] ?? ($quantity * $unitPrice);

            $item->update([
                'name' => $data['item_name'] ?? $item->name,
                'item_name' => $data['item_name'] ?? $item->item_name,
                'specification' => array_key_exists('specification', $data) ? $data['specification'] : $item->specification,
                'quantity' => $quantity,
                'unit' => $data['unit'] ?? $item->unit,
                'unit_price' => $unitPrice,
                'total_amount' => $totalPrice,
                'total_price' => $totalPrice,
                'status' => $newStatus,
                'condition' => $newCondition,
                'serial_number' => array_key_exists('serial_number', $data) ? $data['serial_number'] : $item->serial_number,
                'brand' => array_key_exists('brand', $data) ? $data['brand'] : $item->brand,
                'supplier_name' => array_key_exists('supplier_name', $data) ? $data['supplier_name'] : $item->supplier_name,
                'latitude' => array_key_exists('latitude', $data) ? $data['latitude'] : $item->latitude,
                'longitude' => array_key_exists('longitude', $data) ? $data['longitude'] : $item->longitude,
                'location_name' => array_key_exists('location_address', $data) ? $data['location_address'] : $item->location_name,
                'location_address' => array_key_exists('location_address', $data) ? $data['location_address'] : $item->location_address,
                'location_notes' => array_key_exists('location_notes', $data) ? $data['location_notes'] : $item->location_notes,
                'photos' => array_key_exists('photos', $data) ? $data['photos'] : $item->photos,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $item->notes,
            ]);

            // If status, condition, or location changed, record history
            if ($newStatus !== $prevStatus || $newCondition !== $prevCondition || isset($data['latitude']) || isset($data['location_address'])) {
                RealizationItemHistory::create([
                    'realization_item_id' => $item->id,
                    'action' => 'UPDATE',
                    'status' => $newStatus->value,
                    'actor_id' => $actor->id,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'notes' => $data['history_notes'] ?? 'Pembaruan data/kondisi/lokasi item realisasi.',
                    'metadata' => [
                        'previous_status' => $prevStatus->value,
                        'new_status' => $newStatus->value,
                        'previous_condition' => $prevCondition->value,
                        'new_condition' => $newCondition->value,
                        'location_address' => $item->location_address,
                    ],
                ]);
            }

            // Recalculate package sum
            $item->package->update([
                'total_amount' => $item->package->items()->sum('total_amount'),
            ]);

            $this->auditLogService->record(
                action: 'realization_item.updated',
                module: 'realization',
                entityType: RealizationItem::class,
                entityId: $item->id,
                oldValues: $oldValues,
                newValues: $item->toArray(),
                actorId: $actor->id
            );

            return $item->fresh(['package', 'budgetItem', 'qrIdentity', 'histories']);
        });
    }

    /**
     * Inspect item in field survey or monitoring.
     */
    public function recordInspection(RealizationItem $item, User $actor, array $inspectionData): RealizationItem
    {
        return DB::transaction(function () use ($item, $actor, $inspectionData) {
            $prevCondition = $item->condition;
            $newCondition = isset($inspectionData['condition'])
                ? (is_string($inspectionData['condition']) ? (RealizationItemCondition::tryFrom(strtolower($inspectionData['condition'])) ?? RealizationItemCondition::from($inspectionData['condition'])) : $inspectionData['condition'])
                : $prevCondition;

            $item->update([
                'condition' => $newCondition,
                'status' => RealizationItemStatus::MONITORED,
                'latitude' => $inspectionData['latitude'] ?? $item->latitude,
                'longitude' => $inspectionData['longitude'] ?? $item->longitude,
                'location_name' => $inspectionData['location_address'] ?? $item->location_name,
                'location_address' => $inspectionData['location_address'] ?? $item->location_address,
                'notes' => $inspectionData['notes'] ?? $item->notes,
            ]);

            RealizationItemHistory::create([
                'realization_item_id' => $item->id,
                'action' => $inspectionData['action'] ?? 'FIELD_INSPECTION',
                'status' => RealizationItemStatus::MONITORED->value,
                'actor_id' => $actor->id,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'photos' => $inspectionData['photos'] ?? null,
                'notes' => $inspectionData['notes'] ?? 'Pemeriksaan fisik langsung di lapangan.',
                'metadata' => [
                    'previous_status' => $item->status->value,
                    'new_status' => RealizationItemStatus::MONITORED->value,
                    'previous_condition' => $prevCondition->value,
                    'new_condition' => $newCondition->value,
                    'location_address' => $item->location_address,
                ],
            ]);

            $this->auditLogService->record(
                action: 'realization_item.inspected',
                module: 'realization',
                entityType: RealizationItem::class,
                entityId: $item->id,
                newValues: [
                    'condition' => $newCondition->value,
                    'status' => RealizationItemStatus::MONITORED->value,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                ],
                actorId: $actor->id
            );

            return $item->fresh(['package', 'histories', 'qrIdentity']);
        });
    }
}
