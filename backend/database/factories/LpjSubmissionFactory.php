<?php

namespace Database\Factories;

use App\Enums\LpjStatus;
use App\Models\GrantProgram;
use App\Models\LpjSubmission;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LpjSubmission>
 */
class LpjSubmissionFactory extends Factory
{
    protected $model = LpjSubmission::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'organization_id' => Organization::factory(),
            'grant_program_id' => GrantProgram::factory(),
            'submitted_by' => User::factory(),
            'lpj_number' => 'LPJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => LpjStatus::DRAFT,
            'total_received' => 50000000,
            'total_spent' => 48000000,
            'remaining_balance' => 2000000,
            'summary' => 'Laporan Pertanggungjawaban Realisasi Penggunaan Dana Bantuan Hibah.',
            'notes' => fake()->sentence(),
            'submitted_at' => null,
            'verified_at' => null,
            'approved_at' => null,
            'closed_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LpjStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LpjStatus::APPROVED,
            'submitted_at' => now()->subDays(2),
            'verified_at' => now()->subDay(),
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LpjStatus::FINALIZED,
            'submitted_at' => now()->subDays(3),
            'verified_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'approved_by' => User::factory(),
        ]);
    }
}
