<?php

namespace Tests\Feature\Api\V1;

use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProposalSubmitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\MasterDataSeeder::class,
        ]);

        GrantProgram::query()->firstOrCreate(
            [
                'code' => 'PROGRAM-TEST',
            ],
            [
                'name' => 'Program Hibah Pengujian',
                'description' => 'Program hibah untuk pengujian API.',
                'fiscal_year' => now()->year,
                'status' => 'open',
                'registration_start_date' => now()
                    ->startOfYear()
                    ->toDateString(),
                'registration_end_date' => now()
                    ->endOfYear()
                    ->toDateString(),
                'minimum_amount' => 100000,
                'maximum_amount' => 10000000,
                'total_budget' => 100000000,
                'is_active' => true,
            ]
        );
    }

    private function createApplicant(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::query()
            ->where('code', 'PEMOHON')
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([
            $role->id,
        ]);

        return $user;
    }

    private function createDraftProposal(User $applicant): Proposal
    {
        $program = GrantProgram::query()
            ->where('code', 'PROGRAM-TEST')
            ->where('is_active', true)
            ->firstOrFail();

        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $applicant->id,
            'created_by' => $applicant->id,
            'updated_by' => $applicant->id,
            'status' => 'draft',
            'title' => 'Proposal Pengujian Submit',
            'requested_amount' => 1000000,
        ]);

        ProposalBudgetItem::query()->create([
            'proposal_id' => $proposal->id,
            'category' => 'Operasional',
            'item_name' => 'Perlengkapan kegiatan',
            'description' => 'Item RAB untuk pengujian submit proposal.',
            'quantity' => 1,
            'unit' => 'paket',
            'unit_price' => 1000000,
            'subtotal' => 1000000,
            'sort_order' => 1,
        ]);

        return $proposal->fresh();
    }

    public function test_owner_can_submit_draft_proposal(): void
    {
        $applicant = $this->createApplicant();
        $proposal = $this->createDraftProposal($applicant);

        Sanctum::actingAs($applicant);

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/submit",
            [],
            [
                'X-Request-ID' => 'test-submit-request',
            ]
        );

        $response
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Proposal berhasil diajukan.'
            );

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('proposal_status_histories', [
            'proposal_id' => $proposal->id,
            'to_status' => 'submitted',
            'changed_by' => $applicant->id,
        ]);
    }

    public function test_applicant_cannot_submit_another_applicants_proposal(): void
    {
        $owner = $this->createApplicant();
        $otherApplicant = $this->createApplicant();

        $proposal = $this->createDraftProposal($owner);

        Sanctum::actingAs($otherApplicant);

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/submit"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('proposals', [
            'id' => $proposal->id,
            'status' => 'draft',
        ]);
    }

    public function test_submitted_proposal_cannot_be_submitted_again(): void
    {
        $applicant = $this->createApplicant();
        $proposal = $this->createDraftProposal($applicant);

        $proposal->forceFill([
            'status' => 'submitted',
        ])->save();

        Sanctum::actingAs($applicant);

        $response = $this->postJson(
            "/api/v1/proposals/{$proposal->id}/submit"
        );

        $response->assertForbidden();
    }
}