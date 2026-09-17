<?php

namespace Database\Factories;

use App\Models\DecisionTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DecisionTemplate>
 */
class DecisionTemplateFactory extends Factory
{
    protected $model = DecisionTemplate::class;

    public function definition(): array
    {
        return [
            'code' => 'SK_' . Str::upper(Str::random(6)),
            'name' => 'Template Surat Keputusan',
            'document_type' => 'decision_letter',
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}

