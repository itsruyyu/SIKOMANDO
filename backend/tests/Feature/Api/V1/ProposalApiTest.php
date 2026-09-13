<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProposalStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
        ]);
    }

    private function createPemohon(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::where('code', 'PEMOHON')->firstOrFail();

        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_guest_cannot_access_proposals(): void
    {
        $this
            ->getJson('/api/v1/proposals')
            ->assertUnauthorized();
    }

    public function test_pemohon_can_access_proposal_index(): void
    {
        $user = $this->createPemohon();

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->getJson('/api/v1/proposals')
            ->assertOk();
    }

    public function test_pemohon_cannot_create_proposal_without_required_data(): void
    {
        $user = $this->createPemohon();

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/proposals', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'grant_program_id',
                'organization_id',
                'title',
            ]);
    }

    public function test_public_user_can_view_active_grant_programs(): void
    {
        $this
            ->getJson('/api/v1/grant-programs')
            ->assertOk();
    }

    public function test_pemohon_cannot_view_another_pemohon_proposal(): void
    {
        $userA = $this->createPemohon();
        $userB = $this->createPemohon();

        $program = GrantProgram::factory()->create([
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);

        $organization = Organization::factory()->create([
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $userB->id,
            'title' => 'Proposal milik User B',
            'requested_amount' => 10000000,
            'status' => \App\Enums\ProposalStatus::DRAFT,
            'revision_count' => 0,
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);

        $token = $userA
            ->createToken('phpunit')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->getJson("/api/v1/proposals/{$proposal->id}")
            ->assertForbidden();
    }

    public function test_pemohon_only_sees_own_proposals(): void
    {
        $userA = User::factory()->create([
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'is_active' => true,
        ]);

        $role = Role::where('code', 'PEMOHON')->firstOrFail();

        $userA->roles()->attach($role->id);
        $userB->roles()->attach($role->id);

        $program = GrantProgram::query()->create([
            'code' => 'TEST-PROGRAM-' . uniqid(),
            'name' => 'Program Test',
            'fiscal_year' => now()->year,
            'status' => 'active',
            'is_active' => true,
        ]);

        $organization = Organization::query()->create([
            'code' => 'TEST-ORG-' . uniqid(),
            'name' => 'Organisasi Test',
            'organization_type' => 'organization',
            'is_active' => true,
            'created_by' => $userA->id,
            'updated_by' => $userA->id,
        ]);

        Proposal::query()->create([
            'proposal_number' => 'TEST-A-' . uniqid(),
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $userA->id,
            'title' => 'Proposal User A',
            'requested_amount' => 10000000,
            'status' => \App\Enums\ProposalStatus::DRAFT,
            'revision_count' => 0,
            'created_by' => $userA->id,
            'updated_by' => $userA->id,
        ]);

        Proposal::query()->create([
            'proposal_number' => 'TEST-B-' . uniqid(),
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $userB->id,
            'title' => 'Proposal User B',
            'requested_amount' => 15000000,
            'status' => \App\Enums\ProposalStatus::DRAFT,
            'revision_count' => 0,
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);

        $token = $userA->createToken('phpunit')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/proposals');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Proposal User A');
    }
}

?>