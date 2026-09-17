<?php

namespace App\Services;

use App\Enums\FieldSurveyFindingSeverity;
use App\Enums\FieldSurveyFindingStatus;
use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Enums\ProposalStatus;
use App\Models\FieldSurvey;
use App\Models\FieldSurveyDocument;
use App\Models\FieldSurveyFinding;
use App\Models\FieldSurveyItem;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FieldSurveyService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly ProposalWorkflowService $proposalWorkflowService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function paginateForProposal(
        Proposal $proposal,
        int $perPage = 15,
        ?User $user = null,
    ): LengthAwarePaginator {
        $query = FieldSurvey::query()
            ->with([
                'surveyor:id,name,email',
                'items',
                'findings',
                'documents',
            ])
            ->where('proposal_id', $proposal->id);

        if ($user !== null && $user->hasRole('SURVEYOR') && ! $user->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'APPROVER'])) {
            $query->where('surveyor_id', $user->id);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function paginateForSurveyor(
        User $surveyor,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return FieldSurvey::query()
            ->with([
                'proposal:id,proposal_number,title,status',
                'items',
                'findings',
                'documents',
            ])
            ->where('surveyor_id', $surveyor->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findForProposal(
        Proposal $proposal,
        FieldSurvey|string $survey,
    ): FieldSurvey {
        $surveyId = $survey instanceof FieldSurvey ? $survey->id : $survey;

        return FieldSurvey::query()
            ->with([
                'proposal',
                'surveyor:id,name,email',
                'items',
                'findings',
                'documents.documentType',
                'documents.uploader:id,name,email',
            ])
            ->where('proposal_id', $proposal->id)
            ->whereKey($surveyId)
            ->firstOrFail();
    }

    public function create(
        Proposal $proposal,
        User|string $surveyor,
        array $data = [],
        ?User $actor = null,
    ): FieldSurvey {
        $surveyorId = $surveyor instanceof User ? (string) $surveyor->id : (string) $surveyor;

        $proposalStatus = $proposal->status instanceof \BackedEnum
            ? $proposal->status->value
            : (string) $proposal->status;

        $eligibleStatuses = [
            ProposalStatus::EVALUATION->value,
            ProposalStatus::SURVEY->value,
            ProposalStatus::RECOMMENDED->value,
        ];

        if (! in_array($proposalStatus, $eligibleStatuses, true)) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal belum memenuhi syarat status untuk pelaksanaan survei lapangan.',
            ]);
        }

        return $this->database->transaction(function () use (
            $proposal,
            $proposalStatus,
            $surveyorId,
            $data,
        ): FieldSurvey {
            $activeExists = FieldSurvey::query()
                ->where('proposal_id', $proposal->id)
                ->whereNotIn('status', [
                    FieldSurveyStatus::COMPLETED->value,
                    FieldSurveyStatus::REJECTED->value,
                ])
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'survey' => 'Proposal sudah memiliki survei lapangan yang sedang aktif.',
                ]);
            }

            $survey = FieldSurvey::query()->create([
                'proposal_id' => $proposal->id,
                'surveyor_id' => $surveyorId,
                'survey_number' => $this->generateSurveyNumber($proposal),
                'status' => FieldSurveyStatus::ASSIGNED,
                'result' => FieldSurveyResult::PENDING,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'location_name' => $data['location_name'] ?? null,
                'location_address' => $data['location_address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $checklistItems = [
                [
                    'item_code' => 'SRV-LOC',
                    'item_name' => 'Kesesuaian Lokasi dan Keberadaan Fisik Organisasi',
                    'description' => 'Pemeriksaan keabsahan lokasi fisik dan sekretariat organisasi pemohon.',
                ],
                [
                    'item_code' => 'SRV-OBJ',
                    'item_name' => 'Kelayakan Fisik Sasaran Kegiatan / Objek Hibah',
                    'description' => 'Pemeriksaan kondisi riil objek atau lokasi sasaran bantuan hibah.',
                ],
                [
                    'item_code' => 'SRV-MGT',
                    'item_name' => 'Keabsahan dan Keberadaan Pengurus Pemohon',
                    'description' => 'Konfirmasi tatap muka dengan pengurus pemohon di lokasi kegiatan.',
                ],
                [
                    'item_code' => 'SRV-BDG',
                    'item_name' => 'Kesesuaian Kebutuhan Anggaran dengan Realitas Lapangan',
                    'description' => 'Verifikasi kewajaran usulan biaya terhadap kondisi nyata lapangan.',
                ],
            ];

            foreach ($checklistItems as $itemData) {
                FieldSurveyItem::query()->create([
                    'field_survey_id' => $survey->id,
                    'item_code' => $itemData['item_code'],
                    'item_name' => $itemData['item_name'],
                    'description' => $itemData['description'],
                    'result' => FieldSurveyItemResult::PENDING,
                ]);
            }

            if ($proposalStatus === ProposalStatus::EVALUATION->value) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::SURVEY,
                    actorId: (string) ($actor?->id ?? auth()->id()),
                    reason: 'Penugasan survei lapangan proposal.',
                    notes: sprintf('Survey number: %s', $survey->survey_number),
                );
            }

            $this->auditLogService->record(
                action: 'field_survey.created',
                module: 'field_survey',
                entityType: FieldSurvey::class,
                entityId: $survey->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'surveyor_id' => $surveyorId,
                    'survey_number' => $survey->survey_number,
                    'status' => $survey->status->value,
                ],
                metadata: [
                    'proposal_number' => $proposal->proposal_number,
                ],
            );

            return $survey->load([
                'proposal',
                'surveyor:id,name,email',
                'items',
                'findings',
                'documents',
            ]);
        });
    }

    public function updateSchedule(
        FieldSurvey $survey,
        array $data,
    ): FieldSurvey {
        if ($survey->status->isFinal()) {
            throw ValidationException::withMessages([
                'survey' => 'Survei lapangan yang sudah final tidak dapat diubah.',
            ]);
        }

        $nextStatus = $survey->status === FieldSurveyStatus::ASSIGNED
            ? FieldSurveyStatus::SCHEDULED
            : $survey->status;

        $survey->update([
            'scheduled_date' => $data['scheduled_date'] ?? $survey->scheduled_date,
            'location_name' => $data['location_name'] ?? $survey->location_name,
            'location_address' => $data['location_address'] ?? $survey->location_address,
            'latitude' => $data['latitude'] ?? $survey->latitude,
            'longitude' => $data['longitude'] ?? $survey->longitude,
            'notes' => $data['notes'] ?? $survey->notes,
            'status' => $nextStatus,
        ]);

        $this->auditLogService->record(
            action: 'field_survey.scheduled',
            module: 'field_survey',
            entityType: FieldSurvey::class,
            entityId: $survey->id,
            newValues: [
                'scheduled_date' => $survey->scheduled_date?->toDateString(),
                'location_name' => $survey->location_name,
                'status' => $survey->status->value,
            ],
        );

        return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items']);
    }

    public function start(FieldSurvey $survey): FieldSurvey
    {
        if ($survey->status->isFinal()) {
            throw ValidationException::withMessages([
                'survey' => 'Survei lapangan yang sudah final tidak dapat dimulai ulang.',
            ]);
        }

        $survey->update([
            'status' => FieldSurveyStatus::IN_PROGRESS,
            'started_at' => $survey->started_at ?? now(),
        ]);

        $this->auditLogService->record(
            action: 'field_survey.started',
            module: 'field_survey',
            entityType: FieldSurvey::class,
            entityId: $survey->id,
            newValues: [
                'status' => FieldSurveyStatus::IN_PROGRESS->value,
                'started_at' => $survey->started_at?->toISOString(),
            ],
        );

        return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items']);
    }

    public function updateItem(
        FieldSurvey $survey,
        FieldSurveyItem $item,
        array $data,
        ?User $actor = null,
    ): FieldSurveyItem {
        if ($survey->status->isFinal() || $survey->status === FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Item survei lapangan tidak dapat diubah pada status ini.',
            ]);
        }

        if ((string) $item->field_survey_id !== (string) $survey->id) {
            throw ValidationException::withMessages([
                'item' => 'Item tidak sesuai dengan survei lapangan ini.',
            ]);
        }

        $oldValues = [
            'result' => $item->result instanceof \BackedEnum ? $item->result->value : $item->result,
            'notes' => $item->notes,
        ];

        $rawResult = $data['result'] ?? FieldSurveyItemResult::PASS->value;
        $result = $rawResult instanceof FieldSurveyItemResult
            ? $rawResult
            : FieldSurveyItemResult::from($rawResult);

        $item->update([
            'result' => $result,
            'notes' => $data['notes'] ?? $item->notes,
            'checked_by' => $actor?->id ?? auth()->id(),
            'checked_at' => now(),
        ]);

        if (in_array($survey->status, [FieldSurveyStatus::ASSIGNED, FieldSurveyStatus::SCHEDULED], true)) {
            $survey->update([
                'status' => FieldSurveyStatus::IN_PROGRESS,
                'started_at' => $survey->started_at ?? now(),
            ]);
        }

        $this->auditLogService->record(
            action: 'field_survey.item_updated',
            module: 'field_survey',
            entityType: FieldSurveyItem::class,
            entityId: $item->id,
            oldValues: $oldValues,
            newValues: [
                'result' => $result->value,
                'notes' => $item->notes,
                'checked_at' => $item->checked_at?->toISOString(),
            ],
            metadata: [
                'field_survey_id' => $survey->id,
            ],
        );

        return $item->fresh(['fieldSurvey', 'checker:id,name,email']);
    }

    public function addFinding(
        FieldSurvey $survey,
        array $data,
    ): FieldSurveyFinding {
        if ($survey->status->isFinal() || $survey->status === FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Temuan survei tidak dapat ditambahkan pada status ini.',
            ]);
        }

        $rawSeverity = $data['severity'] ?? FieldSurveyFindingSeverity::MEDIUM->value;
        $severity = $rawSeverity instanceof FieldSurveyFindingSeverity
            ? $rawSeverity
            : FieldSurveyFindingSeverity::from($rawSeverity);

        $finding = FieldSurveyFinding::query()->create([
            'field_survey_id' => $survey->id,
            'finding_code' => $data['finding_code'] ?? 'FND-'.Str::upper(Str::random(6)),
            'finding_type' => $data['finding_type'] ?? 'general',
            'title' => $data['title'],
            'description' => $data['description'],
            'severity' => $severity,
            'recommended_action' => $data['recommended_action'] ?? null,
            'status' => FieldSurveyFindingStatus::OPEN,
        ]);

        $this->auditLogService->record(
            action: 'field_survey.finding_created',
            module: 'field_survey',
            entityType: FieldSurveyFinding::class,
            entityId: $finding->id,
            newValues: [
                'title' => $finding->title,
                'severity' => $finding->severity->value,
                'status' => $finding->status->value,
            ],
            metadata: [
                'field_survey_id' => $survey->id,
            ],
        );

        return $finding;
    }

    public function addDocument(
        FieldSurvey $survey,
        array $data,
        ?User $actor = null,
    ): FieldSurveyDocument {
        if ($survey->status->isFinal() || $survey->status === FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Dokumen survei tidak dapat ditambahkan pada status ini.',
            ]);
        }

        $document = FieldSurveyDocument::query()->create([
            'field_survey_id' => $survey->id,
            'document_type_id' => $data['document_type_id'] ?? null,
            'uploaded_by' => $actor?->id ?? auth()->id(),
            'document_title' => $data['document_title'],
            'original_filename' => $data['original_filename'],
            'stored_filename' => $data['stored_filename'] ?? basename($data['storage_path']),
            'disk' => $data['disk'] ?? 'public',
            'storage_path' => $data['storage_path'],
            'mime_type' => $data['mime_type'] ?? 'image/jpeg',
            'file_size' => $data['file_size'] ?? 0,
            'file_hash' => $data['file_hash'] ?? null,
            'version' => $data['version'] ?? 1,
            'status' => $data['status'] ?? 'uploaded',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditLogService->record(
            action: 'field_survey.document_uploaded',
            module: 'field_survey',
            entityType: FieldSurveyDocument::class,
            entityId: $document->id,
            newValues: [
                'document_title' => $document->document_title,
                'original_filename' => $document->original_filename,
                'storage_path' => $document->storage_path,
            ],
            metadata: [
                'field_survey_id' => $survey->id,
            ],
        );

        return $document;
    }

    public function fillResult(
        FieldSurvey $survey,
        array $data,
    ): FieldSurvey {
        if ($survey->status->isFinal() || $survey->status === FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Hasil survei tidak dapat diubah pada status ini.',
            ]);
        }

        $survey->update([
            'summary' => $data['summary'] ?? $survey->summary,
            'recommendation' => $data['recommendation'] ?? $survey->recommendation,
            'notes' => $data['notes'] ?? $survey->notes,
            'location_name' => $data['location_name'] ?? $survey->location_name,
            'latitude' => $data['latitude'] ?? $survey->latitude,
            'longitude' => $data['longitude'] ?? $survey->longitude,
        ]);

        $this->auditLogService->record(
            action: 'field_survey.result_updated',
            module: 'field_survey',
            entityType: FieldSurvey::class,
            entityId: $survey->id,
            newValues: [
                'summary' => $survey->summary,
                'recommendation' => $survey->recommendation,
            ],
        );

        return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items', 'findings', 'documents']);
    }

    public function submit(
        FieldSurvey $survey,
        ?string $notes = null,
    ): FieldSurvey {
        if ($survey->status->isFinal() || $survey->status === FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Survei lapangan sudah berada pada status final atau diajukan.',
            ]);
        }

        $survey->loadMissing('items');

        $pendingCount = $survey->items
            ->filter(fn (FieldSurveyItem $item): bool => $item->result === FieldSurveyItemResult::PENDING
                || ($item->result?->value ?? $item->result) === FieldSurveyItemResult::PENDING->value
            )
            ->count();

        if ($pendingCount > 0) {
            throw ValidationException::withMessages([
                'items' => 'Seluruh item pemeriksaan survei lapangan harus dinilai terlebih dahulu.',
            ]);
        }

        if (empty($survey->summary) && empty($notes)) {
            throw ValidationException::withMessages([
                'summary' => 'Ringkasan hasil survei lapangan wajib diisi sebelum pengajuan.',
            ]);
        }

        $survey->update([
            'status' => FieldSurveyStatus::SUBMITTED,
            'notes' => $notes ?? $survey->notes,
        ]);

        $this->auditLogService->record(
            action: 'field_survey.submitted',
            module: 'field_survey',
            entityType: FieldSurvey::class,
            entityId: $survey->id,
            newValues: [
                'status' => FieldSurveyStatus::SUBMITTED->value,
            ],
        );

        return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items', 'findings', 'documents']);
    }

    public function review(
        FieldSurvey $survey,
        string $action,
        ?string $notes = null,
        ?User $reviewer = null,
    ): FieldSurvey {
        if ($survey->status !== FieldSurveyStatus::SUBMITTED) {
            throw ValidationException::withMessages([
                'survey' => 'Survei lapangan harus berada pada status diajukan (submitted) untuk ditinjau.',
            ]);
        }

        if ($action === 'request_revision') {
            $survey->update([
                'status' => FieldSurveyStatus::REVISION_REQUIRED,
                'notes' => $notes ?? $survey->notes,
            ]);

            $this->auditLogService->record(
                action: 'field_survey.revision_requested',
                module: 'field_survey',
                entityType: FieldSurvey::class,
                entityId: $survey->id,
                newValues: [
                    'status' => FieldSurveyStatus::REVISION_REQUIRED->value,
                    'notes' => $notes,
                ],
            );
        } else {
            $survey->update([
                'status' => FieldSurveyStatus::REVIEWED,
                'notes' => $notes ?? $survey->notes,
            ]);

            $this->auditLogService->record(
                action: 'field_survey.reviewed',
                module: 'field_survey',
                entityType: FieldSurvey::class,
                entityId: $survey->id,
                newValues: [
                    'status' => FieldSurveyStatus::REVIEWED->value,
                    'notes' => $notes,
                ],
            );
        }

        return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items', 'findings', 'documents']);
    }

    public function complete(
        FieldSurvey $survey,
        array $data = [],
        ?User $actor = null,
    ): FieldSurvey {
        return $this->database->transaction(function () use ($survey, $data, $actor): FieldSurvey {
            $survey->loadMissing(['proposal', 'items']);

            if ($survey->status->isFinal()) {
                throw ValidationException::withMessages([
                    'survey' => 'Survei lapangan sudah berada pada status final.',
                ]);
            }

            $rawResult = $data['result'] ?? FieldSurveyResult::RECOMMENDED->value;
            $result = $rawResult instanceof FieldSurveyResult
                ? $rawResult
                : FieldSurveyResult::from($rawResult);

            $status = $result === FieldSurveyResult::NOT_RECOMMENDED
                ? FieldSurveyStatus::REJECTED
                : FieldSurveyStatus::COMPLETED;

            $survey->update([
                'status' => $status,
                'result' => $result,
                'summary' => $data['summary'] ?? $survey->summary,
                'recommendation' => $data['recommendation'] ?? $survey->recommendation,
                'completed_at' => now(),
            ]);

            $proposal = $survey->proposal;
            if ($proposal !== null) {
                $proposalStatus = $proposal->status instanceof \BackedEnum
                    ? $proposal->status->value
                    : (string) $proposal->status;

                if ($proposalStatus === ProposalStatus::SURVEY->value) {
                    $targetProposalStatus = $result === FieldSurveyResult::RECOMMENDED
                        ? ProposalStatus::RECOMMENDED
                        : ProposalStatus::REJECTED;

                    $this->proposalWorkflowService->transition(
                        proposal: $proposal,
                        targetStatus: $targetProposalStatus,
                        actorId: (string) ($actor?->id ?? auth()->id()),
                        reason: $result === FieldSurveyResult::RECOMMENDED
                            ? 'Survei lapangan selesai dan proposal direkomendasikan.'
                            : 'Survei lapangan selesai dan proposal tidak direkomendasikan.',
                        notes: sprintf('Field Survey ID: %s; Result: %s', $survey->id, $result->value),
                    );
                }
            }

            $this->auditLogService->record(
                action: $status === FieldSurveyStatus::COMPLETED ? 'field_survey.completed' : 'field_survey.rejected',
                module: 'field_survey',
                entityType: FieldSurvey::class,
                entityId: $survey->id,
                newValues: [
                    'status' => $status->value,
                    'result' => $result->value,
                    'completed_at' => $survey->completed_at?->toISOString(),
                ],
                metadata: [
                    'proposal_id' => $survey->proposal_id,
                ],
            );

            return $survey->fresh(['proposal', 'surveyor:id,name,email', 'items', 'findings', 'documents']);
        });
    }

    public function delete(FieldSurvey $survey): void
    {
        if ($survey->status->isFinal()) {
            throw ValidationException::withMessages([
                'survey' => 'Survei lapangan yang sudah final tidak dapat dihapus.',
            ]);
        }

        $this->database->transaction(function () use ($survey): void {
            $surveyId = $survey->id;
            $oldValues = [
                'proposal_id' => $survey->proposal_id,
                'surveyor_id' => $survey->surveyor_id,
                'survey_number' => $survey->survey_number,
                'status' => $survey->status->value,
            ];

            $survey->documents()->delete();
            $survey->findings()->delete();
            $survey->items()->delete();
            $survey->delete();

            $this->auditLogService->record(
                action: 'field_survey.deleted',
                module: 'field_survey',
                entityType: FieldSurvey::class,
                entityId: $surveyId,
                oldValues: $oldValues,
            );
        });
    }

    private function generateSurveyNumber(Proposal $proposal): string
    {
        $prefix = 'SRV-'.now()->format('Ymd');
        $count = FieldSurvey::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return sprintf(
            '%s-%s-%04d',
            $prefix,
            strtoupper(substr((string) $proposal->id, 0, 8)),
            $count,
        );
    }
}
