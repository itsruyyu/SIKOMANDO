<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProposalStatus;
use App\Enums\VerificationItemResult;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $verifier;

    private GrantProgram $grantProgram;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::query()
            ->where('email', 'admin@sikomando.test')
            ->firstOrFail();

        $this->verifier = User::factory()->create([
            'email' => 'verifier@sikomando.test',
        ]);

        $verifierRole = Role::query()
            ->where('name', 'VERIFIKATOR')
            ->orWhere('code', 'VERIFIKATOR')
            ->firstOrFail();

        $this->verifier->roles()->syncWithoutDetaching([
            $verifierRole->id,
        ]);

        $this->grantProgram = GrantProgram::factory()->create();

        $organization = Organization::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'organization_id' => $organization->id,
            'applicant_id' => $this->admin->id,
            'status' => ProposalStatus::VERIFICATION,
        ]);
    }

    public function test_verifier_can_create_verification_for_proposal(): void
    {
        Sanctum::actingAs($this->verifier);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.proposal_id', $this->proposal->id)
            ->assertJsonPath('data.verifier_id', $this->verifier->id);

        $this->assertDatabaseHas('verifications', [
            'proposal_id' => $this->proposal->id,
            'verifier_id' => $this->verifier->id,
        ]);
    }

    public function test_verification_items_are_created_from_program_requirements(): void
    {
        $requirement = Requirement::query()->firstOrFail();

        $this->grantProgram->requirements()->syncWithoutDetaching([
            $requirement->id,
        ]);

        Sanctum::actingAs($this->verifier);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $response->assertCreated();

        $this->assertDatabaseHas('verification_items', [
            'requirement_id' => $requirement->id,
            'result' => VerificationItemResult::PENDING->value,
        ]);
    }

    public function test_user_can_view_verification_detail(): void
    {
        Sanctum::actingAs($this->verifier);

        $createResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $createResponse->assertCreated();

        $verificationId = $createResponse->json('data.id');

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications/{$verificationId}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $verificationId)
            ->assertJsonPath('data.proposal_id', $this->proposal->id);
    }

    public function test_verification_cannot_be_completed_while_items_are_pending(): void
    {
        Sanctum::actingAs($this->verifier);

        $createResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $createResponse->assertCreated();

        $verificationId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications/{$verificationId}/complete"
        );

        $response->assertStatus(422);
    }

    public function test_verification_item_can_be_updated(): void
    {
        $requirement = Requirement::query()->firstOrFail();

        $this->grantProgram->requirements()->syncWithoutDetaching([
            $requirement->id,
        ]);

        Sanctum::actingAs($this->verifier);

        $createResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $createResponse->assertCreated();

        $verificationId = $createResponse->json('data.id');

        $itemId = $createResponse->json('data.items.0.id');

        $this->assertNotNull($itemId);

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications/{$verificationId}/items/{$itemId}",
            [
                'result' => VerificationItemResult::PASS->value,
                'notes' => 'Dokumen telah diverifikasi.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.result',
                VerificationItemResult::PASS->value
            );

        $this->assertDatabaseHas('verification_items', [
            'id' => $itemId,
            'result' => VerificationItemResult::PASS->value,
            'checked_by' => $this->verifier->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_verification_api(): void
    {
        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/verifications"
        );

        $response->assertUnauthorized();
    }
}
