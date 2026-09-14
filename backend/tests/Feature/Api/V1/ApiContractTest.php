<?php

namespace Tests\Feature\Api\V1;

use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContractTest extends TestCase
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

        $role = Role::query()
            ->where('code', 'PEMOHON')
            ->firstOrFail();

        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_grant_program_index_uses_standard_response_contract(): void
    {
        $response = $this->getJson('/api/v1/grant-programs');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Daftar program hibah berhasil diambil.'
            )
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_proposal_index_uses_standard_response_contract(): void
    {
        $user = $this->createPemohon();

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/proposals');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Daftar proposal berhasil diambil.'
            )
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_proposal_store_uses_standard_response_contract(): void
    {
        $user = $this->createPemohon();

        $program = GrantProgram::query()->create([
            'code' => 'CONTRACT-PROGRAM-' . uniqid(),
            'name' => 'Program Contract Test',
            'fiscal_year' => now()->year,
            'status' => 'active',
            'is_active' => true,
        ]);

        $organization = Organization::query()->create([
            'code' => 'CONTRACT-ORG-' . uniqid(),
            'name' => 'Organisasi Contract Test',
            'organization_type' => 'organization',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/proposals', [
                'grant_program_id' => $program->id,
                'organization_id' => $organization->id,
                'title' => 'Proposal Contract Test',
                'background' => 'Latar belakang proposal.',
                'objectives' => 'Tujuan proposal.',
                'benefits' => 'Manfaat proposal.',
                'activities' => 'Kegiatan proposal.',
                'expected_outputs' => 'Output proposal.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Proposal berhasil dibuat.'
            )
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    public function test_proposal_show_uses_standard_response_contract(): void
    {
        $user = $this->createPemohon();

        $program = GrantProgram::query()->create([
            'code' => 'SHOW-PROGRAM-' . uniqid(),
            'name' => 'Program Show Contract',
            'fiscal_year' => now()->year,
            'status' => 'active',
            'is_active' => true,
        ]);

        $organization = Organization::query()->create([
            'code' => 'SHOW-ORG-' . uniqid(),
            'name' => 'Organisasi Show Contract',
            'organization_type' => 'organization',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $proposal = Proposal::query()->create([
            'proposal_number' => 'SHOW-' . uniqid(),
            'grant_program_id' => $program->id,
            'organization_id' => $organization->id,
            'applicant_id' => $user->id,
            'title' => 'Proposal Show Contract',
            'requested_amount' => 10000000,
            'status' => 'draft',
            'revision_count' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson("/api/v1/proposals/{$proposal->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Detail proposal berhasil diambil.'
            )
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}