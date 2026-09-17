<?php

namespace Database\Factories;

use App\Enums\EvaluationItemResult;
use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationItem;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'evaluator_id' => User::factory(),
            'evaluation_number' => 'EVAL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => EvaluationStatus::IN_PROGRESS,
            'total_score' => 0,
            'final_score' => 0,
            'result' => EvaluationResult::PENDING,
            'summary' => null,
            'notes' => fake()->sentence(),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Evaluation $evaluation): void {
            $criteria = EvaluationCriteria::query()
                ->where('is_active', true)
                ->get();

            if ($criteria->isEmpty()) {
                $criteria = collect([
                    EvaluationCriteria::query()->create([
                        'code' => 'CRITERIA-'.Str::upper(Str::random(8)),
                        'name' => 'Kriteria Evaluasi Default',
                        'description' => 'Kriteria evaluasi default.',
                        'criterion_type' => 'score',
                        'default_weight' => 100,
                        'minimum_score' => 0,
                        'maximum_score' => 100,
                        'is_active' => true,
                    ]),
                ]);
            }

            foreach ($criteria as $criterion) {
                EvaluationItem::query()->firstOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'evaluation_criteria_id' => $criterion->id,
                    ],
                    [
                        'weight' => $criterion->default_weight ?? 50,
                        'score' => null,
                        'weighted_score' => null,
                        'minimum_score' => $criterion->minimum_score,
                        'maximum_score' => $criterion->maximum_score,
                        'result' => EvaluationItemResult::PENDING,
                    ]
                );
            }

            $evaluation->unsetRelation('items');
        });
    }
}
