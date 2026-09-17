<?php

namespace App\Services;

use App\Models\EvaluationWeightConfiguration;
use App\Models\GrantProgram;
use App\Models\PolicyConfiguration;
use App\Models\PolicyVersion;
use App\Models\RankingRuleConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PolicyConfigurationService
{
    public function getActiveEvaluationWeights(
        string $grantProgramId
    ): Collection {
        $policyVersion = $this->getActiveEvaluationPolicyVersion();

        $query = EvaluationWeightConfiguration::query()
            ->with('criteria')
            ->where('grant_program_id', $grantProgramId)
            ->where('is_active', true);

        if ($policyVersion !== null) {
            $query->where(function ($query) use ($policyVersion) {
                $query
                    ->where('policy_version_id', $policyVersion->id)
                    ->orWhereNull('policy_version_id');
            });
        } else {
            $query->whereNull('policy_version_id');
        }

        $weights = $query
            ->orderBy('sort_order')
            ->orderBy('evaluation_criteria_id')
            ->get();

        if ($weights->isEmpty()) {
            throw ValidationException::withMessages([
                'grant_program_id' => [
                    'Konfigurasi bobot evaluasi aktif belum tersedia untuk program hibah tersebut.',
                ],
            ]);
        }

        $this->validateWeights($weights);

        return $weights;
    }

    public function getActiveEvaluationPolicyVersion(): ?PolicyVersion
    {
        return $this->getActivePolicyVersionByCode('EVALUATION');
    }

    public function getActiveRankingPolicyVersion(): ?PolicyVersion
    {
        return $this->getActivePolicyVersionByCode('RANKING');
    }

    public function getActivePolicyVersionByCode(string $code): ?PolicyVersion
    {
        $configuration = PolicyConfiguration::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($configuration === null) {
            return null;
        }

        return $configuration->versions()
            ->where('status', 'approved')
            ->where(function ($query) {
                $query
                    ->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('version_number')
            ->first();
    }

    public function getActiveRankingRule(string $grantProgramId): ?RankingRuleConfiguration
    {
        $policyVersion = $this->getActiveRankingPolicyVersion();

        $query = RankingRuleConfiguration::query()
            ->where('grant_program_id', $grantProgramId)
            ->whereIn('status', ['active', 'approved']);

        if ($policyVersion !== null) {
            $query->where(function ($q) use ($policyVersion) {
                $q->where('policy_version_id', $policyVersion->id)
                    ->orWhereNull('policy_version_id');
            });
        }

        return $query->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->first();
    }

    public function resolveRankingParameters(GrantProgram $grantProgram): array
    {
        $ruleConfig = $this->getActiveRankingRule($grantProgram->id);
        $policyVersion = $this->getActiveRankingPolicyVersion();

        $definition = $ruleConfig?->rule_definition ?? [];

        // Fallback or override from PolicyVersion configuration_data if present
        if (empty($definition) && $policyVersion !== null && ! empty($policyVersion->configuration_data['ranking_rule'])) {
            $definition = $policyVersion->configuration_data['ranking_rule'];
        }

        $rawEvalWeight = $definition['evaluation_weight'] ?? 60.0;
        $rawSurveyWeight = $definition['survey_weight'] ?? 40.0;

        $total = (float) $rawEvalWeight + (float) $rawSurveyWeight;
        if ($total <= 0) {
            $total = 100.0;
            $rawEvalWeight = 60.0;
            $rawSurveyWeight = 40.0;
        }

        $normalizedEvalWeight = round((float) $rawEvalWeight / $total, 4);
        $normalizedSurveyWeight = round((float) $rawSurveyWeight / $total, 4);

        $passingGrade = isset($definition['passing_grade']) ? (float) $definition['passing_grade'] : 70.0;
        $minimumEvaluationScore = isset($definition['minimum_evaluation_score']) ? (float) $definition['minimum_evaluation_score'] : 60.0;
        $minimumSurveyScore = isset($definition['minimum_survey_score']) ? (float) $definition['minimum_survey_score'] : 60.0;
        $quota = isset($definition['quota']) ? (int) $definition['quota'] : null;
        $tieBreaker = $definition['tie_breaker'] ?? ['evaluation_score', 'created_at'];

        return [
            'rule_configuration_id' => $ruleConfig?->id,
            'policy_version_id' => $ruleConfig?->policy_version_id ?? $policyVersion?->id,
            'rule_code' => $ruleConfig?->rule_code ?? 'DEFAULT_RULE',
            'rule_name' => $ruleConfig?->rule_name ?? 'Aturan Perangkingan Standar (Evaluasi 60%, Survei 40%)',
            'evaluation_weight' => (float) $rawEvalWeight,
            'survey_weight' => (float) $rawSurveyWeight,
            'normalized_evaluation_weight' => $normalizedEvalWeight,
            'normalized_survey_weight' => $normalizedSurveyWeight,
            'passing_grade' => $passingGrade,
            'minimum_evaluation_score' => $minimumEvaluationScore,
            'minimum_survey_score' => $minimumSurveyScore,
            'quota' => $quota,
            'tie_breaker' => (array) $tieBreaker,
            'rule_definition' => $definition,
        ];
    }

    public function buildEvaluationItems(
        string $grantProgramId
    ): array {
        $weights = $this->getActiveEvaluationWeights($grantProgramId);

        return $weights
            ->map(function (EvaluationWeightConfiguration $weight) {
                $criteria = $weight->criteria;

                if ($criteria === null) {
                    throw ValidationException::withMessages([
                        'evaluation_criteria_id' => [
                            'Kriteria evaluasi pada konfigurasi bobot tidak ditemukan.',
                        ],
                    ]);
                }

                return [
                    'evaluation_criteria_id' => $criteria->id,
                    'weight' => $weight->weight,
                    'minimum_score' => $weight->minimum_score
                        ?? $criteria->minimum_score,
                    'maximum_score' => $weight->maximum_score
                        ?? $criteria->maximum_score,
                    'result' => null,
                    'score' => null,
                    'weighted_score' => null,
                    'notes' => null,
                    'scored_at' => null,
                ];
            })
            ->values()
            ->all();
    }

    private function validateWeights(Collection $weights): void
    {
        $totalWeight = $weights->sum(
            fn (EvaluationWeightConfiguration $weight): float => (float) $weight->weight
        );

        if ($totalWeight <= 0) {
            throw ValidationException::withMessages([
                'weight' => [
                    'Total bobot evaluasi harus lebih besar dari nol.',
                ],
            ]);
        }

        foreach ($weights as $weight) {
            if ((float) $weight->weight < 0) {
                throw ValidationException::withMessages([
                    'weight' => [
                        'Bobot evaluasi tidak boleh bernilai negatif.',
                    ],
                ]);
            }

            $minimumScore = $weight->minimum_score
                ?? $weight->criteria?->minimum_score;

            $maximumScore = $weight->maximum_score
                ?? $weight->criteria?->maximum_score;

            if (
                $minimumScore !== null
                && $maximumScore !== null
                && (float) $minimumScore > (float) $maximumScore
            ) {
                throw ValidationException::withMessages([
                    'minimum_score' => [
                        'Minimum score tidak boleh lebih besar dari maximum score.',
                    ],
                ]);
            }
        }
    }
}
