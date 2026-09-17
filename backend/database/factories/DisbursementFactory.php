<?php

namespace Database\Factories;

use App\Enums\DisbursementStatus;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Disbursement>
 */
class DisbursementFactory extends Factory
{
    protected $model = Disbursement::class;

    public function definition(): array
    {
        return [
            'disbursement_plan_id' => DisbursementPlan::factory(),
            'proposal_id' => fn (array $attributes) => DisbursementPlan::find($attributes['disbursement_plan_id'])?->proposal_id ?? Proposal::factory(),
            'stage_number' => 1,
            'disbursement_number' => 'DISB-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'planned_amount' => 50000000,
            'approved_amount' => null,
            'paid_amount' => 0,
            'status' => DisbursementStatus::PLANNED,
            'planned_date' => now()->addDays(7)->toDateString(),
            'approved_date' => null,
            'paid_date' => null,
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Yayasan Peduli Sesama',
            'notes' => fake()->sentence(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisbursementStatus::VERIFIED,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisbursementStatus::APPROVED,
            'approved_amount' => $attributes['planned_amount'] ?? 50000000,
            'approved_date' => now()->toDateString(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisbursementStatus::PAID,
            'approved_amount' => $attributes['planned_amount'] ?? 50000000,
            'paid_amount' => $attributes['planned_amount'] ?? 50000000,
            'approved_date' => now()->subDay()->toDateString(),
            'paid_date' => now()->toDateString(),
        ]);
    }
}
