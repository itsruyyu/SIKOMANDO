<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Enums\RankingStatus;
use App\Enums\RecommendationResult;
use App\Enums\RecommendationStatus;
use App\Models\FieldSurvey;
use App\Models\GrantProgram;
use App\Models\Proposal;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\Recommendation;
use App\Models\RecommendationItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RankingService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly PolicyConfigurationService $policyConfigurationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function preview(
        GrantProgram $grantProgram,
        ?array $overrides = null,
    ): array {
        return $this->calculateRanking($grantProgram, $overrides);
    }

    public function generate(
        GrantProgram $grantProgram,
        User $actor,
        array $data = [],
    ): Ranking {
        $calculation = $this->calculateRanking($grantProgram);

        if (empty($calculation['items'])) {
            throw ValidationException::withMessages([
                'candidates' => 'Tidak ada proposal yang memenuhi syarat (evaluasi selesai dan survei final) untuk diperingkat.',
            ]);
        }

        return $this->database->transaction(function () use (
            $grantProgram,
            $actor,
            $data,
            $calculation,
        ): Ranking {
            $rankingNumber = $this->generateRankingNumber($grantProgram);

            $ranking = Ranking::query()->create([
                'grant_program_id' => $grantProgram->id,
                'ranking_rule_configuration_id' => $calculation['parameters']['rule_configuration_id'] ?? null,
                'policy_version_id' => $calculation['parameters']['policy_version_id'] ?? null,
                'ranking_number' => $rankingNumber,
                'status' => RankingStatus::GENERATED,
                'weights_snapshot' => $calculation['parameters'],
                'cutoff_score' => $calculation['parameters']['passing_grade'],
                'total_proposals' => $calculation['statistics']['total_candidates'],
                'recommended_count' => $calculation['statistics']['recommended_count'],
                'not_recommended_count' => $calculation['statistics']['not_recommended_count'],
                'summary' => $data['summary'] ?? sprintf('Perangkingan program %s berhasil dihasilkan.', $grantProgram->name),
                'notes' => $data['notes'] ?? null,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ]);

            foreach ($calculation['items'] as $itemData) {
                RankingItem::query()->create([
                    'ranking_id' => $ranking->id,
                    'proposal_id' => $itemData['proposal_id'],
                    'rank' => $itemData['rank'],
                    'evaluation_score' => $itemData['evaluation_score'],
                    'survey_score' => $itemData['survey_score'],
                    'final_score' => $itemData['final_score'],
                    'status' => $itemData['status'],
                    'recommendation_result' => $itemData['recommendation_result'],
                    'recommendation_reason' => $itemData['recommendation_reason'],
                    'notes' => $itemData['notes'] ?? null,
                    'snapshot_data' => $itemData['snapshot_data'],
                ]);
            }

            $this->auditLogService->record(
                action: 'ranking.generated',
                module: 'ranking',
                entityType: Ranking::class,
                entityId: $ranking->id,
                newValues: [
                    'ranking_number' => $ranking->ranking_number,
                    'grant_program_id' => $grantProgram->id,
                    'total_proposals' => $ranking->total_proposals,
                    'recommended_count' => $ranking->recommended_count,
                    'status' => $ranking->status->value,
                ],
                metadata: [
                    'grant_program_code' => $grantProgram->code,
                ],
            );

            return $ranking->load([
                'grantProgram:id,code,name,fiscal_year',
                'generator:id,name,email',
                'items.proposal:id,proposal_number,title,applicant_id',
            ]);
        });
    }

    public function regenerate(
        Ranking $ranking,
        User $actor,
        array $data = [],
    ): Ranking {
        if (! $ranking->status->canBeRegenerated()) {
            throw ValidationException::withMessages([
                'ranking' => 'Ranking yang sudah final tidak dapat di-regenerate.',
            ]);
        }

        return $this->database->transaction(function () use (
            $ranking,
            $actor,
            $data,
        ): Ranking {
            $grantProgram = $ranking->grantProgram;
            $calculation = $this->calculateRanking($grantProgram);

            $ranking->items()->delete();

            foreach ($calculation['items'] as $itemData) {
                RankingItem::query()->create([
                    'ranking_id' => $ranking->id,
                    'proposal_id' => $itemData['proposal_id'],
                    'rank' => $itemData['rank'],
                    'evaluation_score' => $itemData['evaluation_score'],
                    'survey_score' => $itemData['survey_score'],
                    'final_score' => $itemData['final_score'],
                    'status' => $itemData['status'],
                    'recommendation_result' => $itemData['recommendation_result'],
                    'recommendation_reason' => $itemData['recommendation_reason'],
                    'notes' => $itemData['notes'] ?? null,
                    'snapshot_data' => $itemData['snapshot_data'],
                ]);
            }

            $oldValues = [
                'total_proposals' => $ranking->total_proposals,
                'recommended_count' => $ranking->recommended_count,
            ];

            $ranking->update([
                'status' => RankingStatus::GENERATED,
                'weights_snapshot' => $calculation['parameters'],
                'cutoff_score' => $calculation['parameters']['passing_grade'],
                'total_proposals' => $calculation['statistics']['total_candidates'],
                'recommended_count' => $calculation['statistics']['recommended_count'],
                'not_recommended_count' => $calculation['statistics']['not_recommended_count'],
                'summary' => $data['summary'] ?? $ranking->summary,
                'notes' => $data['notes'] ?? $ranking->notes,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ]);

            $this->auditLogService->record(
                action: 'ranking.regenerated',
                module: 'ranking',
                entityType: Ranking::class,
                entityId: $ranking->id,
                oldValues: $oldValues,
                newValues: [
                    'total_proposals' => $ranking->total_proposals,
                    'recommended_count' => $ranking->recommended_count,
                    'status' => $ranking->status->value,
                ],
            );

            return $ranking->fresh([
                'grantProgram:id,code,name,fiscal_year',
                'generator:id,name,email',
                'items.proposal:id,proposal_number,title,applicant_id',
            ]);
        });
    }

    public function review(
        Ranking $ranking,
        User $actor,
        array $data,
    ): Ranking {
        if ($ranking->status->isFinal()) {
            throw ValidationException::withMessages([
                'ranking' => 'Ranking yang sudah final tidak dapat ditinjau ulang.',
            ]);
        }

        $ranking->update([
            'status' => RankingStatus::REVIEWED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'notes' => $data['notes'] ?? $ranking->notes,
        ]);

        $this->auditLogService->record(
            action: 'ranking.reviewed',
            module: 'ranking',
            entityType: Ranking::class,
            entityId: $ranking->id,
            newValues: [
                'status' => RankingStatus::REVIEWED->value,
                'reviewed_by' => $actor->id,
                'notes' => $ranking->notes,
            ],
        );

        return $ranking->fresh([
            'grantProgram:id,code,name,fiscal_year',
            'generator:id,name,email',
            'reviewer:id,name,email',
            'items.proposal:id,proposal_number,title,applicant_id',
        ]);
    }

    public function finalize(
        Ranking $ranking,
        User $actor,
        array $data = [],
    ): Ranking {
        if ($ranking->status->isFinal()) {
            throw ValidationException::withMessages([
                'ranking' => 'Ranking sudah berada pada status final.',
            ]);
        }

        return $this->database->transaction(function () use (
            $ranking,
            $actor,
            $data,
        ): Ranking {
            $ranking->loadMissing(['items.proposal', 'grantProgram']);

            $ranking->update([
                'status' => RankingStatus::FINALIZED,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
                'notes' => $data['notes'] ?? $ranking->notes,
            ]);

            // Sinkronisasi data ke tabel recommendations dan recommendation_items untuk setiap proposal
            foreach ($ranking->items as $item) {
                $proposal = $item->proposal;
                if ($proposal === null) {
                    continue;
                }

                $recResult = $item->recommendation_result === 'recommended'
                    ? RecommendationResult::RECOMMENDED
                    : RecommendationResult::NOT_RECOMMENDED;

                $recommendation = Recommendation::query()->updateOrCreate(
                    [
                        'proposal_id' => $proposal->id,
                    ],
                    [
                        'recommended_by' => $actor->id,
                        'recommendation_number' => 'REC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                        'status' => RecommendationStatus::COMPLETED,
                        'result' => $recResult,
                        'recommended_amount' => $recResult === RecommendationResult::RECOMMENDED ? $proposal->requested_amount : 0,
                        'summary' => sprintf('Rekomendasi resmi program %s peringkat %d dengan nilai akhir %.2f.', $ranking->grantProgram->name, $item->rank, $item->final_score),
                        'reason' => $item->recommendation_reason,
                        'notes' => $item->notes,
                        'completed_at' => now(),
                    ]
                );

                $recommendation->items()->delete();

                // 1. Evaluasi item
                RecommendationItem::query()->create([
                    'recommendation_id' => $recommendation->id,
                    'item_code' => 'EVAL',
                    'item_name' => 'Hasil Evaluasi Proposal',
                    'source_type' => 'evaluation',
                    'source_id' => $item->snapshot_data['evaluation']['id'] ?? null,
                    'result' => $item->snapshot_data['evaluation']['result'] ?? 'completed',
                    'description' => sprintf('Nilai evaluasi: %.2f (bobot %.0f%%)', $item->evaluation_score, $ranking->weights_snapshot['evaluation_weight'] ?? 60),
                ]);

                // 2. Survei item
                RecommendationItem::query()->create([
                    'recommendation_id' => $recommendation->id,
                    'item_code' => 'SURVEY',
                    'item_name' => 'Hasil Survei Lapangan',
                    'source_type' => 'field_survey',
                    'source_id' => $item->snapshot_data['field_survey']['id'] ?? null,
                    'result' => $item->snapshot_data['field_survey']['result'] ?? 'completed',
                    'description' => sprintf('Nilai survei: %.2f (bobot %.0f%%)', $item->survey_score, $ranking->weights_snapshot['survey_weight'] ?? 40),
                ]);

                // 3. Final ranking item
                RecommendationItem::query()->create([
                    'recommendation_id' => $recommendation->id,
                    'item_code' => 'FINAL_RANK',
                    'item_name' => 'Peringkat dan Skor Akhir Perangkingan',
                    'source_type' => 'ranking',
                    'source_id' => $ranking->id,
                    'result' => $item->recommendation_result,
                    'description' => sprintf('Peringkat ke-%d dengan nilai akhir %.2f', $item->rank, $item->final_score),
                ]);
            }

            $this->auditLogService->record(
                action: 'ranking.finalized',
                module: 'ranking',
                entityType: Ranking::class,
                entityId: $ranking->id,
                newValues: [
                    'status' => RankingStatus::FINALIZED->value,
                    'finalized_by' => $actor->id,
                    'finalized_at' => $ranking->finalized_at?->toISOString(),
                ],
            );

            return $ranking->fresh([
                'grantProgram:id,code,name,fiscal_year',
                'generator:id,name,email',
                'reviewer:id,name,email',
                'finalizer:id,name,email',
                'items.proposal:id,proposal_number,title,applicant_id',
            ]);
        });
    }

    public function paginateForProgram(
        GrantProgram $grantProgram,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Ranking::query()
            ->with([
                'grantProgram:id,code,name,fiscal_year',
                'generator:id,name,email',
                'finalizer:id,name,email',
            ])
            ->where('grant_program_id', $grantProgram->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findForProgram(
        GrantProgram $grantProgram,
        Ranking|string $ranking,
    ): Ranking {
        $rankingId = $ranking instanceof Ranking ? $ranking->id : $ranking;

        return Ranking::query()
            ->with([
                'grantProgram:id,code,name,fiscal_year',
                'generator:id,name,email',
                'reviewer:id,name,email',
                'finalizer:id,name,email',
                'items.proposal:id,proposal_number,title,applicant_id,requested_amount',
            ])
            ->where('grant_program_id', $grantProgram->id)
            ->whereKey($rankingId)
            ->firstOrFail();
    }

    public function getProposalRecommendation(Proposal $proposal): ?Recommendation
    {
        return Recommendation::query()
            ->with(['recommender:id,name,email', 'items'])
            ->where('proposal_id', $proposal->id)
            ->latest('created_at')
            ->first();
    }

    private function calculateRanking(
        GrantProgram $grantProgram,
        ?array $overrides = null,
    ): array {
        $params = $this->policyConfigurationService->resolveRankingParameters($grantProgram);
        if ($overrides !== null) {
            $params = array_merge($params, $overrides);
        }

        $evalWeight = (float) $params['normalized_evaluation_weight'];
        $surveyWeight = (float) $params['normalized_survey_weight'];
        $passingGrade = (float) $params['passing_grade'];
        $minEval = (float) $params['minimum_evaluation_score'];
        $minSurvey = (float) $params['minimum_survey_score'];
        $quota = $params['quota'];

        // Ambil proposal pada program ini
        $proposals = Proposal::query()
            ->where('grant_program_id', $grantProgram->id)
            ->with([
                'applicant:id,name,email',
                'evaluations' => fn ($q) => $q->latest('created_at'),
                'fieldSurveys' => fn ($q) => $q->with('items')->latest('created_at'),
            ])
            ->get();

        $candidates = [];

        foreach ($proposals as $proposal) {
            $latestEvaluation = $proposal->evaluations->first();
            $latestSurvey = $proposal->fieldSurveys->first();

            // Aturan 1: Proposal dengan evaluasi belum selesai (tidak COMPLETED) tidak masuk ranking
            if ($latestEvaluation === null || $latestEvaluation->status !== EvaluationStatus::COMPLETED) {
                continue;
            }

            // Aturan 2: Proposal dengan survei belum final tidak masuk ranking
            if ($latestSurvey === null || ! $latestSurvey->status?->isFinal()) {
                continue;
            }

            // Hitung skor evaluasi
            $evalScore = (float) ($latestEvaluation->final_score ?? $latestEvaluation->total_score ?? 0);

            // Hitung skor survei
            $surveyScore = $this->calculateSurveyScore($latestSurvey);

            // Hitung nilai akhir
            $finalScore = round(($evalScore * $evalWeight) + ($surveyScore * $surveyWeight), 4);

            // Evaluasi kelayakan
            $isSurveyRecommended = $latestSurvey->result === FieldSurveyResult::RECOMMENDED;
            $meetsScores = $evalScore >= $minEval && $surveyScore >= $minSurvey && $finalScore >= $passingGrade;
            $isEligible = $isSurveyRecommended && $meetsScores;

            $recommendationReason = null;
            if (! $isSurveyRecommended) {
                $recommendationReason = 'Tidak direkomendasikan karena hasil survei lapangan tidak merekomendasikan.';
            } elseif (! $meetsScores) {
                $recommendationReason = sprintf(
                    'Nilai akhir (%.2f) di bawah batas kelulusan (%.2f).',
                    $finalScore,
                    $passingGrade
                );
            } else {
                $recommendationReason = sprintf(
                    'Memenuhi batas kelulusan dengan nilai akhir %.2f.',
                    $finalScore
                );
            }

            $snapshotData = [
                'proposal' => [
                    'id' => $proposal->id,
                    'proposal_number' => $proposal->proposal_number,
                    'title' => $proposal->title,
                    'requested_amount' => $proposal->requested_amount,
                    'applicant_name' => $proposal->applicant?->name,
                ],
                'evaluation' => [
                    'id' => $latestEvaluation->id,
                    'evaluation_number' => $latestEvaluation->evaluation_number,
                    'score' => $evalScore,
                    'result' => $latestEvaluation->result?->value,
                    'completed_at' => $latestEvaluation->completed_at?->toISOString(),
                ],
                'field_survey' => [
                    'id' => $latestSurvey->id,
                    'survey_number' => $latestSurvey->survey_number,
                    'score' => $surveyScore,
                    'result' => $latestSurvey->result?->value,
                    'status' => $latestSurvey->status?->value,
                    'completed_at' => $latestSurvey->completed_at?->toISOString(),
                ],
                'calculation' => [
                    'eval_weight' => $evalWeight,
                    'survey_weight' => $surveyWeight,
                    'passing_grade' => $passingGrade,
                    'formula' => sprintf('(%.2f * %.2f) + (%.2f * %.2f) = %.4f', $evalScore, $evalWeight, $surveyScore, $surveyWeight, $finalScore),
                ],
            ];

            $candidates[] = [
                'proposal_id' => $proposal->id,
                'proposal_number' => $proposal->proposal_number,
                'proposal_title' => $proposal->title,
                'applicant_name' => $proposal->applicant?->name,
                'requested_amount' => (float) ($proposal->requested_amount ?? 0),
                'created_at' => $proposal->created_at?->timestamp ?? 0,
                'evaluation_score' => $evalScore,
                'survey_score' => $surveyScore,
                'final_score' => $finalScore,
                'is_eligible' => $isEligible,
                'status' => $isEligible ? 'eligible' : 'ineligible',
                'recommendation_result' => $isEligible ? 'recommended' : 'not_recommended',
                'recommendation_reason' => $recommendationReason,
                'snapshot_data' => $snapshotData,
            ];
        }

        // Pengurutan ranking: Nilai akhir DESC, tie-breaker: evaluation_score DESC, requested_amount ASC, created_at ASC
        usort($candidates, function (array $a, array $b): int {
            if ($a['final_score'] !== $b['final_score']) {
                return $b['final_score'] <=> $a['final_score'];
            }
            if ($a['evaluation_score'] !== $b['evaluation_score']) {
                return $b['evaluation_score'] <=> $a['evaluation_score'];
            }
            if ($a['requested_amount'] !== $b['requested_amount']) {
                return $a['requested_amount'] <=> $b['requested_amount'];
            }

            return $a['created_at'] <=> $b['created_at'];
        });

        // Penomoran peringkat dan penyesuaian kuota (jika ada)
        $rankedItems = [];
        $recommendedCount = 0;
        $notRecommendedCount = 0;

        foreach ($candidates as $index => $candidate) {
            $rank = $index + 1;
            $candidate['rank'] = $rank;

            if ($candidate['is_eligible']) {
                if ($quota !== null && $recommendedCount >= $quota) {
                    $candidate['recommendation_result'] = 'not_recommended';
                    $candidate['recommendation_reason'] = sprintf('Tidak direkomendasikan karena melebihi batas kuota (%d proposal).', $quota);
                    $notRecommendedCount++;
                } else {
                    $recommendedCount++;
                }
            } else {
                $notRecommendedCount++;
            }

            $rankedItems[] = $candidate;
        }

        return [
            'grant_program' => [
                'id' => $grantProgram->id,
                'code' => $grantProgram->code,
                'name' => $grantProgram->name,
                'fiscal_year' => $grantProgram->fiscal_year,
            ],
            'parameters' => $params,
            'statistics' => [
                'total_candidates' => count($rankedItems),
                'recommended_count' => $recommendedCount,
                'not_recommended_count' => $notRecommendedCount,
            ],
            'items' => $rankedItems,
        ];
    }

    private function calculateSurveyScore(FieldSurvey $survey): float
    {
        if ($survey->result === FieldSurveyResult::NOT_RECOMMENDED || $survey->status === FieldSurveyStatus::REJECTED) {
            return 0.0;
        }

        $items = $survey->items;
        if ($items->isNotEmpty()) {
            $applicable = $items->filter(
                fn ($i) => ($i->result?->value ?? $i->result) !== FieldSurveyItemResult::NOT_APPLICABLE->value
            );

            if ($applicable->isNotEmpty()) {
                $passed = $applicable->filter(
                    fn ($i) => ($i->result?->value ?? $i->result) === FieldSurveyItemResult::PASS->value
                );

                return round(($passed->count() / $applicable->count()) * 100, 2);
            }
        }

        return 100.0;
    }

    private function generateRankingNumber(GrantProgram $grantProgram): string
    {
        $prefix = 'RNK-'.now()->format('Ymd');
        $count = Ranking::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return sprintf(
            '%s-%s-%04d',
            $prefix,
            strtoupper(substr((string) $grantProgram->id, 0, 8)),
            $count,
        );
    }
}
