<?php

namespace App\Services;

use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Models\MonitoringItem;
use App\Models\MonitoringRecord;
use App\Models\Proposal;
use App\Models\QrIdentity;
use App\Models\RealizationItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonitoringService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NumberingService $numberingService,
        protected QrService $qrService,
        protected RealizationService $realizationService
    ) {}

    public function paginateForProposal(Proposal $proposal, int $perPage = 15): LengthAwarePaginator
    {
        return MonitoringRecord::where('proposal_id', $proposal->id)
            ->with(['inspector', 'items.realizationItem'])
            ->latest('monitoring_date')
            ->paginate($perPage);
    }

    public function createRecord(Proposal $proposal, User $actor, array $data): MonitoringRecord
    {
        return DB::transaction(function () use ($proposal, $actor, $data) {
            $monitoringNumber = $data['monitoring_number'] ?? $this->numberingService->generateNumber(
                'monitoring',
                $proposal->grantProgram
            );

            $record = MonitoringRecord::create([
                'proposal_id' => $proposal->id,
                'monitoring_number' => $monitoringNumber,
                'monitoring_type' => $data['monitoring_type'] ?? 'periodic',
                'monitoring_date' => $data['monitoring_date'] ?? now()->toDateString(),
                'created_by' => $actor->id,
                'status' => 'in_progress',
                'summary' => $data['title'] ?? $data['summary'] ?? 'Monitoring dan Evaluasi Pasca Realisasi',
                'notes' => $data['notes'] ?? null,
                'findings' => $data['overall_result'] ?? $data['findings'] ?? null,
            ]);

            // Generate QR identity for monitoring record
            $this->qrService->generateFor(
                entity: $record,
                type: QrType::MONITORING,
                actor: $actor,
                metadata: [
                    'monitoring_number' => $record->monitoring_number,
                    'proposal_id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                ]
            );

            $this->auditLogService->record(
                action: 'monitoring_record.created',
                module: 'monitoring',
                entityType: MonitoringRecord::class,
                entityId: $record->id,
                newValues: $record->toArray(),
                actorId: $actor->id
            );

            return $record->load(['proposal', 'inspector', 'qrIdentity']);
        });
    }

    /**
     * Record check on a physical item by scanning its QR or providing item ID.
     */
    public function recordItemCheck(MonitoringRecord $record, User $actor, array $data): MonitoringItem
    {
        return DB::transaction(function () use ($record, $actor, $data) {
            /** @var RealizationItem|null $item */
            $item = null;
            /** @var QrIdentity|null $qr */
            $qr = null;
            $token = $data['token'] ?? null;

            if ($token) {
                $verification = $this->qrService->verifyToken(
                    token: $token,
                    actor: $actor,
                    context: 'monitoring'
                );

                $qr = QrIdentity::where('token', $token)->first();
                if ($qr && $qr->qrable_type === RealizationItem::class) {
                    $item = $qr->qrable;
                }
            }

            if (! $item && ! empty($data['realization_item_id'])) {
                $item = RealizationItem::find($data['realization_item_id']);
                if ($item && ! $qr) {
                    $qr = $item->qrIdentity;
                }
            }

            if (! $item) {
                throw ValidationException::withMessages([
                    'item' => 'Item realisasi tidak ditemukan melalui token QR maupun ID yang diberikan.',
                ]);
            }

            // Ensure item belongs to the proposal being monitored
            if ($item->package?->proposal_id !== $record->proposal_id) {
                throw ValidationException::withMessages([
                    'item' => 'Item realisasi ini bukan bagian dari proposal yang sedang dimonitoring.',
                ]);
            }

            $condition = isset($data['condition'])
                ? (is_string($data['condition']) ? (RealizationItemCondition::tryFrom(strtolower($data['condition'])) ?? RealizationItemCondition::GOOD) : $data['condition'])
                : RealizationItemCondition::GOOD;

            $mItem = MonitoringItem::create([
                'monitoring_record_id' => $record->id,
                'realization_item_id' => $item->id,
                'scanned_qr_token' => $token,
                'indicator_code' => $item->item_code ?? 'ITEM-CHECK',
                'indicator_name' => $item->name ?? $item->item_name ?? 'Pemeriksaan Barang Realisasi',
                'description' => 'Verifikasi fisik barang melalui QR Code',
                'status' => 'verified',
                'notes' => $data['notes'] ?? ('Kondisi: '.($condition instanceof RealizationItemCondition ? $condition->value : $condition)),
            ]);

            // Update item physical status and location via RealizationService
            $this->realizationService->recordInspection($item, $actor, [
                'condition' => $condition,
                'action' => 'MONITORING_INSPECTION',
                'latitude' => $data['latitude'] ?? $item->latitude,
                'longitude' => $data['longitude'] ?? $item->longitude,
                'location_address' => $data['location_address'] ?? $item->location_address,
                'photos' => $data['photos'] ?? null,
                'notes' => 'Monitoring berkala: '.($data['notes'] ?? '-'),
            ]);

            $this->auditLogService->record(
                action: 'monitoring_item.checked',
                module: 'monitoring',
                entityType: MonitoringItem::class,
                entityId: $mItem->id,
                newValues: $mItem->toArray(),
                actorId: $actor->id
            );

            return $mItem->load(['realizationItem']);
        });
    }

    public function completeRecord(MonitoringRecord $record, User $actor, array $data): MonitoringRecord
    {
        return DB::transaction(function () use ($record, $actor, $data) {
            $record->update([
                'status' => 'completed',
                'findings' => $data['overall_result'] ?? 'SATISFACTORY',
                'notes' => $data['notes'] ?? $record->notes,
                'verified_at' => now(),
                'verified_by' => $actor->id,
            ]);

            $this->auditLogService->record(
                action: 'monitoring_record.completed',
                module: 'monitoring',
                entityType: MonitoringRecord::class,
                entityId: $record->id,
                newValues: [
                    'status' => 'completed',
                    'overall_result' => $record->overall_result,
                ],
                actorId: $actor->id
            );

            return $record->fresh(['items.realizationItem', 'inspector']);
        });
    }
}
