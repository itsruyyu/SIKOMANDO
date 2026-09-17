<?php

namespace App\Services;

use App\Enums\EvaluationItemResult;
use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationItem;
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
        $evaluationId = $evaluation instanceof Evaluation ? $evaluation->id : $evaluation;

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
        $evaluatorId = $user instanceof User ? (string) $user->id : (string) $user;
        $notes = is_array($data) ? ($data['notes'] ?? null) : (is_string($data) ? $data : null);

        return $this->database->transaction(function () use (
            $proposal,
            $evaluatorId,
            $notes,
        ): Evaluation {
            $existing = Evaluation::query()
                ->where('proposal_id', $proposal->id)
                ->where('evaluator_id', $evaluatorId)
                ->whereIn('status', [
                    EvaluationStatus::DRAFT->value,
                    EvaluationStatus::IN_PROGRESS->value,
                ])
                ->first();

            if ($existing !== null) {
                return $existing->load([
                    'proposal',
                    'evaluator:id,name,email',
                    'items.criteria',
                ]);
            }

            $criteria = EvaluationCriteria::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get();

            if ($criteria->isEmpty()) {
                throw ValidationException::withMessages([
                    'evaluation' => 'Belum tersedia kriteria evaluasi aktif.',
                ]);
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

        if ($item->evaluation_id !== $evaluation->id) {
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
            : null;

        $maximum = $item->maximum_score !== null
            ? (float) $item->maximum_score
            : null;

        if ($minimum !== null && $score < $minimum) {
            throw ValidationException::withMessages([
                'score' => "Nilai tidak boleh kurang dari {$minimum}.",
            ]);
        }

        if ($maximum !== null && $score > $maximum) {
            throw ValidationException::withMessages([
                'score' => "Nilai tidak boleh lebih dari {$maximum}.",
            ]);
        }

        $weight = (float) ($item->weight ?? 0);
        $weightedScore = $score * $weight;

        $item->update([
            'score' => $score,
            'weighted_score' => $weightedScore,
            'result' => EvaluationItemResult::PASS,
            'notes' => $notes,
            'scored_at' => now(),
        ]);

        $this->recalculate($evaluation);

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
            $evaluation->load('items');

            if ($evaluation->status?->isFinal()) {
                throw ValidationException::withMessages([
                    'evaluation' => 'Evaluasi sudah berada pada status final.',
                ]);
            }

            $pendingItems = $evaluation->items
                ->filter(
                    fn (EvaluationItem $item): bool => $item->result === null ||
                        $item->result === EvaluationItemResult::PENDING
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

            return $evaluation->fresh([
                'proposal',
                'evaluator:id,name,email',
                'items.criteria',
            ]);
        });
    }

    public function recalculate(Evaluation $evaluation): Evaluation
    {
        $evaluation->loadMissing('items');

        $items = $evaluation->items;

        $totalScore = (float) $items->sum(
            fn (EvaluationItem $item): float => (float) ($item->score ?? 0)
        );

        $weightedTotal = (float) $items->sum(
            fn (EvaluationItem $item): float => (float) ($item->weighted_score ?? 0)
        );

        $totalWeight = (float) $items->sum(
            fn (EvaluationItem $item): float => (float) ($item->weight ?? 0)
        );

        $finalScore = $totalWeight > 0
            ? $weightedTotal / $totalWeight
            : 0;

        $evaluation->update([
            'total_score' => $totalScore,
            'final_score' => $finalScore,
        ]);

        return $evaluation->fresh();
    }

    private function generateEvaluationNumber(Proposal $proposal): string
    {
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
