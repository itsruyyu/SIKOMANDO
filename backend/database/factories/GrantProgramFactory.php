<?php

namespace Database\Factories;

use App\Models\GrantProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrantProgram>
 */
class GrantProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PROGRAM-'.fake()->unique()->numerify('#######'),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'fiscal_year' => (string) now()->year,
            'status' => 'active',

            'registration_start_at' => now()->startOfYear()->toDateString(),
            'registration_end_at' => now()->endOfYear()->toDateString(),

            'minimum_amount' => 1000000,
            'maximum_amount' => 100000000,
            'total_budget' => 1000000000,

            'is_active' => true,

            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
