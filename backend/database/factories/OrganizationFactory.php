<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'ORG-' . fake()->unique()->numerify('#######'),
            'name' => fake()->company(),
            'organization_type' => 'Perguruan Tinggi',

            'description' => fake()->paragraph(),

            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),

            'province_id' => null,
            'regency_id' => null,
            'district_id' => null,
            'village_id' => null,

            'postal_code' => fake()->numerify('#####'),

            'legal_status' => 'Badan Hukum',
            'registration_number' => fake()->unique()->numerify('REG-########'),

            'is_active' => true,

            'created_by' => null,
            'updated_by' => null,
        ];
    }
}