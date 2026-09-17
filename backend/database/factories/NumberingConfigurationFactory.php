<?php

namespace Database\Factories;

use App\Models\GrantProgram;
use App\Models\NumberingConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NumberingConfiguration>
 */
class NumberingConfigurationFactory extends Factory
{
    protected $model = NumberingConfiguration::class;

    public function definition(): array
    {
        return [
            'grant_program_id' => GrantProgram::factory(),
            'document_type' => 'decision_letter',
            'prefix' => 'SK',
            'format_pattern' => '{prefix}/{year}/{month}/{seq}',
            'current_sequence' => 0,
            'reset_period' => 1,
            'status' => 'active',
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
