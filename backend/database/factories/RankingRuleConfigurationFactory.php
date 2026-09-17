<?php

namespace Database\Factories;

use App\Models\GrantProgram;
use App\Models\RankingRuleConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RankingRuleConfiguration>
 */
class RankingRuleConfigurationFactory extends Factory
{
    protected $model = RankingRuleConfiguration::class;

    public function definition(): array
    {
        return [
            'grant_program_id' => GrantProgram::factory(),
            'policy_version_id' => null,
            'rule_code' => 'RULE-'.Str::upper(Str::random(8)),
            'rule_name' => 'Konfigurasi Aturan Perangkingan '.fake()->words(2, true),
            'rule_definition' => [
                'evaluation_weight' => 60.0,
                'survey_weight' => 40.0,
                'passing_grade' => 70.0,
                'minimum_evaluation_score' => 60.0,
                'minimum_survey_score' => 60.0,
                'quota' => null,
                'tie_breaker' => ['evaluation_score', 'created_at'],
            ],
            'priority' => 10,
            'status' => 'approved',
            'created_by' => User::factory(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ];
    }
}
