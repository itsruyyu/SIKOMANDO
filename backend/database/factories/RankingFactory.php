<?php

namespace Database\Factories;

use App\Enums\RankingStatus;
use App\Models\GrantProgram;
use App\Models\Ranking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ranking>
 */
class RankingFactory extends Factory
{
    protected $model = Ranking::class;

    public function definition(): array
    {
        return [
            'grant_program_id' => GrantProgram::factory(),
            'ranking_rule_configuration_id' => null,
            'policy_version_id' => null,
            'ranking_number' => 'RNK-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => RankingStatus::GENERATED,
            'weights_snapshot' => [
                'evaluation_weight' => 60.0,
                'survey_weight' => 40.0,
                'normalized_evaluation_weight' => 0.6,
                'normalized_survey_weight' => 0.4,
                'passing_grade' => 70.0,
            ],
            'cutoff_score' => 70.0,
            'total_proposals' => 0,
            'recommended_count' => 0,
            'not_recommended_count' => 0,
            'summary' => 'Hasil perangkingan program hibah.',
            'notes' => fake()->sentence(),
            'generated_by' => User::factory(),
            'reviewed_by' => null,
            'finalized_by' => null,
            'generated_at' => now(),
            'reviewed_at' => null,
            'finalized_at' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => [
            'status' => RankingStatus::REVIEWED,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'notes' => 'Telah ditinjau dan diverifikasi.',
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => [
            'status' => RankingStatus::FINALIZED,
            'finalized_by' => User::factory(),
            'finalized_at' => now(),
            'notes' => 'Perangkingan final telah disahkan.',
        ]);
    }
}
