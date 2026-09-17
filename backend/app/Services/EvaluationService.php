<?php

namespace App\Services;

use App\Enums\EvaluationItemResult;
use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Enums\ProposalStatus;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationItem;
use App\Models\EvaluationWeightConfiguration;
use App\Models\PolicyConfiguration;
use App\Models\PolicyVersion;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly ProposalWorkflowService $proposalWorkflowService,
        private readonly PolicyConfigurationService $policyConfigurationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function paginateForProposal(
        Proposal $proposal,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Evaluation::query()
            ->with([
                'evaluator:id,name,email',
                'items.criteria',
            ])
            ->where('proposal_id', $proposal->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findForProposal(
        Proposal $proposal,
        Evaluation|string $evaluation,
    ): Evaluation {
        $evaluationId = $evaluation instanceof Evaluation
            ? $evaluation->id
            : $evaluation;

        return Evaluation::query()
            ->with([
                'proposal',
                'evaluator:id,name,email',
                'items.criteria',
            ])
            ->where('proposal_id', $proposal->id)
            ->whereKey($evaluationId)
            ->firstOrFail();
    }

    public function create(
        Proposal $proposal,
        User|string $user,
        array|string|null $data = null,
    ): Evaluation {
        $evaluatorId = $user instanceof User
            ? (string) $user->id
            : (string) $user;

        $notes = is_array($data)
            ? ($data['notes'] ?? null)
            : (is_string($data) ? $data : null);

        $proposalStatus = $proposal->status instanceof \BackedEnum
            ? $proposal->status->value
            : (string) $proposal->status;

        if (! in_array($proposalStatus, [ProposalStatus::VERIFIED->value, ProposalStatus::EVALUATION->value], true)) {
            throw ValidationException::withMessages([
                'proposal' => 'Proposal harus berada pada status verified atau evaluation untuk dapat dievaluasi.',
            ]);
        }

        return $this->database->transaction(function () use (
            $proposal,
            $proposalStatus,
            $evaluatorId,
            $notes,
        ): Evaluation {
            $existing = Evaluation::query()
                ->where('proposal_id', $proposal->id)
                ->where('evaluator_id', $evaluatorId)
                ->whereIn('status', [
                    EvaluationStatus::DRAFT->value,
                    EvaluationStatus::IN_PROGRESS->value,
                    EvaluationStatus::COMPLETED->value,
                ])
                ->first();

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'evaluation' => 'Evaluator sudah memiliki evaluasi untuk proposal ini.',
                ]);
            }

            $weights = collect();

            $policyConfiguration = PolicyConfiguration::query()
                ->where('code', 'EVALUATION')
                ->where('is_active', true)
                ->first();

            if ($policyConfiguration !== null) {
                $activePolicyVersion = PolicyVersion::query()
                    ->where('policy_configuration_id', $policyConfiguration->id)
                    ->where('status', 'approved')
                    ->where(function ($query): void {
                        $query->whereNull('effective_from')
                            ->orWhere('effective_from', '<=', now());
                    })
                    ->where(function ($query): void {
                        $query->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', now());
                    })
                    ->orderByDesc('effective_from')
                    ->orderByDesc('created_at')
                    ->first();

                if ($activePolicyVersion !== null) {
                    $weights = EvaluationWeightConfiguration::query()
                        ->with('criteria')
                        ->where('grant_program_id', $proposal->grant_program_id)
                        ->where('policy_version_id', $activePolicyVersion->id)
                        ->where('is_active', true)
                        ->whereHas('criteria', function ($query): void {
                            $query->where('is_active', true);
                        })
                        ->orderBy('sort_order')
                        ->orderBy('evaluation_criteria_id')
                        ->get();
                }
            }

            if ($weights->isEmpty()) {
                $criteria = EvaluationCriteria::query()
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get();

                if ($criteria->isEmpty()) {
                    throw ValidationException::withMessages([
                        'evaluation' => 'Belum tersedia kriteria evaluasi aktif.',
                    ]);
                }
            }

            $evaluation = Evaluation::query()->create([
                'proposal_id' => $proposal->id,
                'evaluator_id' => $evaluatorId,
                'evaluation_number' => $this->generateEvaluationNumber($proposal),
                'status' => EvaluationStatus::IN_PROGRESS,
                'total_score' => 0,
                'final_score' => 0,
                'result' => EvaluationResult::PENDING,
                'notes' => $notes,
                'started_at' => now(),
            ]);

            if ($weights->isNotEmpty()) {
                foreach ($weights as $weight) {
                    $criterion = $weight->criteria;

                    EvaluationItem::query()->create([
                        'evaluation_id' => $evaluation->id,
                        'evaluation_criteria_id' => $weight->evaluation_criteria_id,
                        'weight' => $weight->weight,
                        'score' => null,
                        'weighted_score' => null,
                        'minimum_score' => $weight->minimum_score
                            ?? $criterion?->minimum_score,
                        'maximum_score' => $weight->maximum_score
                            ?? $criterion?->maximum_score,
                        'result' => EvaluationItemResult::PENDING,
                    ]);
                }
            } else {
                foreach ($criteria as $criterion) {
                    EvaluationItem::query()->create([
                        'evaluation_id' => $evaluation->id,
                        'evaluation_criteria_id' => $criterion->id,
                        'weight' => $criterion->default_weight ?? 0,
                        'score' => null,
                        'weighted_score' => null,
                        'minimum_score' => $criterion->minimum_score,
                        'maximum_score' => $criterion->maximum_score,
                        'result' => EvaluationItemResult::PENDING,
                    ]);
                }
            }

            if ($proposalStatus === ProposalStatus::VERIFIED->value) {
                $this->proposalWorkflowService->transition(
                    proposal: $proposal,
                    targetStatus: ProposalStatus::EVALUATION,
                    actorId: $evaluatorId,
                    reason: 'Evaluasi proposal dimulai.',
                    notes: sprintf('Evaluation number: %s', $evaluation->evaluation_number),
                );
            }

            $this->auditLogService->record(
                action: 'evaluation.created',
                module: 'evaluation',
                entityType: Evaluation::class,
                entityId: $evaluation->id,
                newValues: [
                    'proposal_id' => $proposal->id,
                    'evaluator_id' => $evaluatorId,
                    'evaluation_number' => $evaluation->evaluation_number,
                    'status' => $evaluation->status?->value ?? $evaluation->status,
                ],
                metadata: [
                    'proposal_number' => $proposal->proposal_number,
                ],
            );

            return $evaluation->load([
                'proposal',
                'evaluator:id,name,email',
                'items.criteria',
            ]);
        });
    }

    public function updateItem(
        Evaluation $evaluation,
        EvaluationItem $item,
        array|float $data,
        ?string $notes = null,
    ): Evaluation {
        if ($evaluation->status?->isFinal()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Evaluasi yang sudah final tidak dapat diubah.',
            ]);
        }

        if ((string) $item->evaluation_id !== (string) $evaluation->id) {
            throw ValidationException::withMessages([
                'item' => 'Item evaluasi tidak sesuai dengan evaluasi ini.',
            ]);
        }

        if (is_array($data)) {
            $score = (float) ($data['score'] ?? 0);
            $notes = $data['notes'] ?? $notes;
        } else {
            $score = (float) $data;
        }

        $minimum = $item->minimum_score !== null
            ? (float) $item->minimum_score
            : 0.0;

        $maximum = $item->maximum_score !== null
            ? (float) $item->maximum_score
            : 100.0;

        if ($score < $minimum || $score > $maximum) {
            throw ValidationException::withMessages([
                'score' => "Nilai harus berada pada rentang {$minimum} sampai {$maximum}.",
            ]);
        }

        $oldValues = [
            'score' => $item->score,
            'weighted_score' => $item->weighted_score,
            'result' => $item->result instanceof \BackedEnum ? $item->result->value : $item->result,
            'notes' => $item->notes,
        ];

        $weight = (float) ($item->weight ?? 0);

        $maximumForCalculation = $maximum > 0
            ? $maximum
            : 100.0;

        $weightedScore = round(
            ($score / $maximumForCalculation) * $weight,
            4,
        );

        $resolvedResult = $score >= $minimum
            ? EvaluationItemResult::PASS
            : EvaluationItemResult::FAIL;

        $item->update([
            'score' => $score,
            'weighted_score' => $weightedScore,
            'result' => $resolvedResult,
            'notes' => $notes,
            'scored_at' => now(),
        ]);

        $this->recalculate($evaluation);

        $this->auditLogService->record(
            action: 'evaluation.item_updated',
            module: 'evaluation',
            entityType: EvaluationItem::class,
            entityId: $item->id,
            oldValues: $oldValues,
            newValues: [
                'score' => $item->score,
                'weighted_score' => $item->weighted_score,
                'result' => $resolvedResult->value,
                'notes' => $item->notes,
                'scored_at' => $item->scored_at?->toISOString(),
            ],
            metadata: [
                'evaluation_id' => $evaluation->id,
                'criteria_id' => $item->evaluation_criteria_id,
            ],
        );

        return $evaluation->fresh([
            'proposal',
            'evaluator:id,name,email',
            'items.criteria',
        ]);
    }

    public function complete(
        Evaluation $evaluation,
        array|string|null $data = null,
        ?string $notes = null,
    ): Evaluation {
        if (is_array($data)) {
            $summary = $data['summary'] ?? null;
            $notes = $data['notes'] ?? $notes;
        } else {
            $summary = $data;
        }

        return $this->database->transaction(function () use (
            $evaluation,
            $summary,
            $notes,
        ): Evaluation {
            $evaluation->load(['items', 'proposal']);

            if ($evaluation->status?->isFinal()) {
                throw ValidationException::withMessages([
                    'evaluation' => 'Evaluasi sudah berada pada status final.',
                ]);
            }

            $pendingItems = $evaluation->items
                ->filter(
                    fn (EvaluationItem $item): bool => $item->score === null
                        || $item->result === null
                        || $item->result === EvaluationItemResult::PENDING,
                );

            if ($pendingItems->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Seluruh item evaluasi harus dinilai terlebih dahulu.',
                ]);
            }

            $this->recalculate($evaluation);

            $evaluation->refresh();

            $finalScore = (float) ($evaluation->final_score ?? 0);

            $result = $finalScore > 0
                ? EvaluationResult::RECOMMENDED
                : EvaluationResult::NOT_RECOMMENDED;

            $evaluation->update([
                'status' => EvaluationStatus::COMPLETED,
                'result' => $result,
                'summary' => $summary,
                'notes' => $notes ?? $evaluation->notes,
                'completed_at' => now(),
            ]);

            $proposal = $evaluation->proposal;
            if ($proposal !== null) {
                $proposalStatus = $proposal->status instanceof \BackedEnum
                    ? $proposal->status->value
                    : (string) $proposal->status;

                if ($proposalStatus === ProposalStatus::VERIFIED->value) {
                    $this->proposalWorkflowService->transition(
                        proposal: $proposal,
                        targetStatus: ProposalStatus::EVALUATION,
                        actorId: (string) auth()->id(),
                        reason: 'Memulai evaluasi proposal.',
                    );
                    $proposalStatus = ProposalStatus::EVALUATION->value;
                }

                if ($proposalStatus === ProposalStatus::EVALUATION->value) {
                    $targetStatus = $result === EvaluationResult::RECOMMENDED
                        ? ProposalStatus::RECOMMENDED
                        : ProposalStatus::REJECTED;

                    $this->proposalWorkflowService->transition(
                        proposal: $proposal,
                        targetStatus: $targetStatus,
                        actorId: (string) auth()->id(),
                        reason: $result === EvaluationResult::RECOMMENDED
                            ? 'Evaluasi selesai dan proposal direkomendasikan.'
                            : 'Evaluasi selesai dan proposal tidak direkomendasikan.',
                        notes: sprintf('Evaluation ID: %s; Final Score: %s', $evaluation->id, $finalScore),
                    );
                }
            }

            $this->auditLogService->record(
                action: 'evaluation.completed',
                module: 'evaluation',
                entityType: Evaluation::class,
                entityId: $evaluation->id,
                oldValues: [
                    'status' => EvaluationStatus::IN_PROGRESS->value,
                    'result' => EvaluationResult::PENDING->value,
                ],
                newValues: [
                    'status' => EvaluationStatus::COMPLETED->value,
                    'result' => $result->value,
                    'total_score' => $evaluation->total_score,
                    'final_score' => $evaluation->final_score,
                    'summary' => $summary,
                    'completed_at' => $evaluation->completed_at?->toISOString(),
                ],
                metadata: [
                    'proposal_id' => $evaluation->proposal_id,
                ],
            );

            return $evaluation->fresh([
                'proposal',
                'evaluator:id,name,email',
                'items.criteria',
            ]);
        });
    }

    public function delete(Evaluation $evaluation): void
    {
        if ($evaluation->status?->isFinal()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Evaluasi yang sudah final tidak dapat dihapus.',
            ]);
        }

        $this->database->transaction(function () use ($evaluation): void {
            $evaluationId = $evaluation->id;
            $oldValues = [
                'proposal_id' => $evaluation->proposal_id,
                'evaluator_id' => $evaluation->evaluator_id,
                'evaluation_number' => $evaluation->evaluation_number,
                'status' => $evaluation->status?->value ?? $evaluation->status,
            ];

            $evaluation->items()->delete();
            $evaluation->delete();

            $this->auditLogService->record(
                action: 'evaluation.deleted',
                module: 'evaluation',
                entityType: Evaluation::class,
                entityId: $evaluationId,
                oldValues: $oldValues,
            );
        });
    }

    public function recalculate(Evaluation $evaluation): Evaluation
    {
        return $this->database->transaction(function () use (
            $evaluation,
        ): Evaluation {
            $evaluation->loadMissing('items');

            $totalScore = 0.0;
            $finalScore = 0.0;

            foreach ($evaluation->items as $item) {
                if ($item->score === null) {
                    continue;
                }

                $score = (float) $item->score;
                $weight = (float) ($item->weight ?? 0);

                $minimumScore = $item->minimum_score !== null
                    ? (float) $item->minimum_score
                    : 0.0;

                $maximumScore = $item->maximum_score !== null
                    ? (float) $item->maximum_score
                    : 100.0;

                if ($maximumScore <= 0) {
                    throw ValidationException::withMessages([
                        'evaluation' => 'Nilai maksimum kriteria evaluasi harus lebih besar dari nol.',
                    ]);
                }

                $weightedScore = round(
                    ($score / $maximumScore) * $weight,
                    4,
                );

                $resolvedResult = $score >= $minimumScore
                    ? EvaluationItemResult::PASS
                    : EvaluationItemResult::FAIL;

                $item->forceFill([
                    'weighted_score' => $weightedScore,
                    'result' => $resolvedResult,
                ])->save();

                $totalScore += $score;
                $finalScore += $weightedScore;
            }

            $evaluation->forceFill([
                'total_score' => round($totalScore, 4),
                'final_score' => round($finalScore, 4),
            ])->save();

            return $evaluation->fresh([
                'proposal',
                'evaluator:id,name,email',
                'items.criteria',
            ]);
        });
    }

    private function generateEvaluationNumber(
        Proposal $proposal,
    ): string {
        $prefix = 'EVAL-'.now()->format('Ymd');

        $count = Evaluation::query()
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
