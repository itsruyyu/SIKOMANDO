<?php

namespace Database\Factories;

use App\Enums\DisbursementPlanStatus;
use App\Models\DisbursementPlan;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DisbursementPlan>
 */
class DisbursementPlanFactory extends Factory
{
    protected $model = DisbursementPlan::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'created_by' => User::factory(),
            'plan_number' => 'PLAN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'total_stages' => 1,
            'planned_amount' => 50000000,
            'status' => DisbursementPlanStatus::DRAFT,
            'notes' => fake()->sentence(),
            'submitted_at' => null,
            'approved_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisbursementPlanStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisbursementPlanStatus::APPROVED,
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);
    }
}
