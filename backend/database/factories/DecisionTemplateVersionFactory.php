<?php

namespace Database\Factories;

use App\Models\DecisionTemplate;
use App\Models\DecisionTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionTemplateVersion>
 */
class DecisionTemplateVersionFactory extends Factory
{
    protected $model = DecisionTemplateVersion::class;

    public function definition(): array
    {
        return [
            'decision_template_id' => DecisionTemplate::factory(),
            'version_number' => '1.0',
            'template_content' => '<p>Surat Keputusan {{decision_number}} untuk {{proposal_title}} sebesar {{approved_amount}}.</p>',
            'status' => 'active',
            'effective_from' => now()->subDay(),
            'effective_until' => null,
            'created_by' => User::factory(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ];
    }
}
