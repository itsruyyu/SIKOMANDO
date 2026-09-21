<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\Role;
use App\Models\SignatureProfile;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteAuthorizationMatrixTest extends TestCase
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

    protected function createUserWithRole(string $roleCode): User
    {
        $role = Role::where('code', $roleCode)->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        return $user;
    }

    public function test_signature_profiles_endpoint_strictly_denies_unauthorized_roles(): void
    {
        $deniedRoles = ['PEMOHON', 'SURVEYOR', 'EVALUATOR', 'VERIFIKATOR'];

        foreach ($deniedRoles as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            Sanctum::actingAs($user);

            // Index
            $this->getJson('/api/v1/signatures/profiles')
                ->assertStatus(403);

            // Store
            $this->postJson('/api/v1/signatures/profiles', [
                'user_id' => $user->id,
                'name' => 'Fake Name',
                'position' => 'Fake Position',
            ])->assertStatus(403);
        }

        // Allowed: SUPER_ADMIN and ADMIN_SIKOMANDO
        $admin = $this->createUserWithRole('ADMIN_SIKOMANDO');
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/signatures/profiles')->assertStatus(200);
    }

    public function test_user_management_strictly_denies_pemohon_and_field_staff(): void
    {
        $deniedRoles = ['PEMOHON', 'SURVEYOR', 'EVALUATOR', 'VERIFIKATOR'];

        foreach ($deniedRoles as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            Sanctum::actingAs($user);

            $this->getJson('/api/v1/users')
                ->assertStatus(403);
        }
    }

    public function test_policy_configurations_strictly_denies_non_admins(): void
    {
        $deniedRoles = ['PEMOHON', 'SURVEYOR', 'EVALUATOR', 'VERIFIKATOR'];

        foreach ($deniedRoles as $roleCode) {
            $user = $this->createUserWithRole($roleCode);
            Sanctum::actingAs($user);

            $this->getJson('/api/v1/policy-configurations')
                ->assertStatus(403);
        }

        // Approver can view configurations but cannot create new versions
        $approver = $this->createUserWithRole('APPROVER');
        Sanctum::actingAs($approver);
        $this->getJson('/api/v1/policy-configurations')->assertStatus(200);
        $this->postJson('/api/v1/policy-configurations/EVALUATION_WEIGHT/versions', [
            'name' => 'Test Version',
        ])->assertStatus(403);

        $admin = $this->createUserWithRole('ADMIN_SIKOMANDO');
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/policy-configurations')->assertStatus(200);
    }
}
