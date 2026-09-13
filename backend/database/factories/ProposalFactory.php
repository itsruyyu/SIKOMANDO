<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'proposal_number' => 'TEST-' . strtoupper(Str::random(10)),

            'grant_program_id' => null,

            'organization_id' => null,

            'applicant_id' => null,

            'title' => fake()->sentence(6),

            'background' => fake()->paragraph(),

            'objectives' => fake()->paragraph(),

            'benefits' => fake()->paragraph(),

            'activities' => fake()->paragraph(),

            'expected_outputs' => fake()->paragraph(),

            'requested_amount' => 10000000,

            'approved_amount' => null,

            'status' => ProposalStatus::DRAFT,

            'revision_count' => 0,

            'submitted_at' => null,

            'verified_at' => null,

            'approved_at' => null,

            'completed_at' => null,

            'created_by' => null,

            'updated_by' => null,
        ];
    }
}
?>