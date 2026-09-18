<?php

namespace Tests\Feature\Api\V1;

use App\Models\PolicyConfiguration;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PolicyGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private User $creatorAdmin;

    private User $approverAdmin;

    private User $pemohon;

    private PolicyConfiguration $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, MasterDataSeeder::class]);

        $this->creatorAdmin = User::factory()->create(['name' => 'Admin Pembuat Draft', 'is_active' => true]);
        $this->creatorAdmin->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->approverAdmin = User::factory()->create(['name' => 'Admin Penyetuju', 'is_active' => true]);
        $this->approverAdmin->roles()->attach(Role::where('code', 'ADMIN_SIKOMANDO')->first());

        $this->pemohon = User::factory()->create(['is_active' => true]);
        $this->pemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->config = PolicyConfiguration::firstOrCreate(
            ['code' => 'RANKING'],
            ['name' => 'Kebijakan Perangkingan', 'is_active' => true]
        );
    }

    public function test_admin_can_create_draft_policy_version(): void
    {
        Sanctum::actingAs($this->creatorAdmin);

        $response = $this->postJson("/api/v1/policy-configurations/{$this->config->code}/versions", [
            'version_number' => 'v2026.99',
            'configuration_data' => [
                'ranking_rule' => [
                    'evaluation_weight' => 70,
                    'survey_weight' => 30,
                ],
            ],
            'effective_from' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version_number', 'v2026.99');

        $this->assertDatabaseHas('policy_versions', [
            'version_number' => 'v2026.99',
            'status' => 'draft',
        ]);
    }

    public function test_maker_checker_rejects_creator_approving_own_policy_version(): void
    {
        Sanctum::actingAs($this->creatorAdmin);

        $version = $this->config->versions()->create([
            'version_number' => 'v2026.100',
            'configuration_data' => ['test' => true],
            'status' => 'draft',
            'created_by' => $this->creatorAdmin->id,
        ]);

        // Same user attempts approval -> must be rejected
        $response = $this->postJson("/api/v1/policy-configurations/versions/{$version->id}/approve", [
            'notes' => 'Mencoba menyetujui sendiri.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['approved_by']);
    }

    public function test_different_admin_can_approve_and_activate_policy_version(): void
    {
        // 1. Creator creates draft
        $version = $this->config->versions()->create([
            'version_number' => 'v2026.101',
            'configuration_data' => ['evaluation_weight' => 50],
            'status' => 'draft',
            'created_by' => $this->creatorAdmin->id,
        ]);

        // 2. Approver approves
        Sanctum::actingAs($this->approverAdmin);

        $approveResponse = $this->postJson("/api/v1/policy-configurations/versions/{$version->id}/approve", [
            'notes' => 'Disetujui oleh pejabat berwenang.',
        ]);

        $approveResponse->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // 3. Activate version
        $activateResponse = $this->postJson("/api/v1/policy-configurations/versions/{$version->id}/activate");

        $activateResponse->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertEquals('active', $version->fresh()->status);
    }

    public function test_pemohon_cannot_access_policy_governance(): void
    {
        Sanctum::actingAs($this->pemohon);

        $this->getJson('/api/v1/policy-configurations')->assertForbidden();
        $this->getJson("/api/v1/policy-configurations/{$this->config->code}/versions")->assertForbidden();
        $this->postJson("/api/v1/policy-configurations/{$this->config->code}/versions", [])->assertForbidden();
    }
}
