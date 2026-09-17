<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminSikomando;

    private User $pemohon;

    private User $verifikator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@sikomando.test',
            'password' => Hash::make('Password'),
            'is_active' => true,
        ]);
        $this->superAdmin->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->adminSikomando = User::factory()->create([
            'name' => 'Admin SIKOMANDO',
            'email' => 'admin@sikomando.test',
            'is_active' => true,
        ]);
        $this->adminSikomando->roles()->attach(Role::where('code', 'ADMIN_SIKOMANDO')->first());

        $this->pemohon = User::factory()->create([
            'name' => 'Pemohon Test',
            'email' => 'pemohon@sikomando.test',
            'is_active' => true,
        ]);
        $this->pemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->verifikator = User::factory()->create([
            'name' => 'Verifikator Test',
            'email' => 'verifikator@sikomando.test',
            'is_active' => true,
        ]);
        $this->verifikator->roles()->attach(Role::where('code', 'VERIFIKATOR')->first());
    }

    // ── Super Admin CRUD ──

    public function test_super_admin_can_list_users(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'is_active', 'roles'],
                    ],
                ],
            ]);
    }

    public function test_super_admin_can_create_user_with_roles(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $payload = [
            'name' => 'Staff Baru',
            'email' => 'staffbaru@sikomando.test',
            'password' => 'Password123!',
            'phone' => '081234567890',
            'is_active' => true,
            'roles' => ['VERIFIKATOR'],
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Staff Baru')
            ->assertJsonPath('data.roles.0.code', 'VERIFIKATOR');

        $this->assertDatabaseHas('users', ['email' => 'staffbaru@sikomando.test']);
    }

    public function test_system_rejects_creating_second_super_admin(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $payload = [
            'name' => 'Super Admin Kedua',
            'email' => 'superadmin2@sikomando.test',
            'password' => 'Password123!',
            'roles' => ['SUPER_ADMIN'],
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['roles']);
    }

    public function test_super_admin_cannot_be_deactivated(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/v1/users/{$this->superAdmin->id}/toggle-active");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user']);
    }

    public function test_super_admin_can_toggle_active_status_of_other_users(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->assertTrue($this->pemohon->is_active);

        $response = $this->postJson("/api/v1/users/{$this->pemohon->id}/toggle-active");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($this->pemohon->fresh()->is_active);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->pemohon->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->pemohon->email,
            'password' => 'Password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_super_admin_can_update_user(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->patchJson("/api/v1/users/{$this->verifikator->id}", [
            'name' => 'Verifikator Updated',
            'phone' => '08999999999',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Verifikator Updated');

        $this->assertDatabaseHas('users', [
            'id' => $this->verifikator->id,
            'name' => 'Verifikator Updated',
        ]);
    }

    public function test_super_admin_can_assign_and_remove_role(): void
    {
        Sanctum::actingAs($this->superAdmin);

        // Assign EVALUATOR role to verifikator
        $assignResponse = $this->postJson("/api/v1/users/{$this->verifikator->id}/assign-role", [
            'role' => 'EVALUATOR',
        ]);

        $assignResponse->assertOk();
        $this->assertTrue($this->verifikator->fresh()->hasRole('EVALUATOR'));

        // Remove VERIFIKATOR role
        $removeResponse = $this->postJson("/api/v1/users/{$this->verifikator->id}/remove-role", [
            'role' => 'VERIFIKATOR',
        ]);

        $removeResponse->assertOk();
        $this->assertFalse($this->verifikator->fresh()->hasRole('VERIFIKATOR'));
    }

    public function test_user_cannot_change_own_role(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/v1/users/{$this->superAdmin->id}/assign-role", [
            'role' => 'AUDITOR',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/v1/users/{$this->verifikator->id}/reset-password", [
            'password' => 'NewPassword123!',
        ]);

        $response->assertOk();

        // Verify login works with new password
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->verifikator->email,
            'password' => 'NewPassword123!',
        ]);

        $loginResponse->assertOk();
    }

    // ── Non-Admin Access Restrictions ──

    public function test_pemohon_cannot_access_user_management(): void
    {
        Sanctum::actingAs($this->pemohon);

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', ['name' => 'Test'])->assertForbidden();
        $this->patchJson("/api/v1/users/{$this->verifikator->id}", ['name' => 'Test'])->assertForbidden();
        $this->postJson("/api/v1/users/{$this->verifikator->id}/toggle-active")->assertForbidden();
    }

    public function test_admin_sikomando_can_list_users_by_role_for_assignments(): void
    {
        Sanctum::actingAs($this->adminSikomando);

        $response = $this->getJson('/api/v1/users/by-role/VERIFIKATOR');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_creation_records_audit_trail(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->postJson('/api/v1/users', [
            'name' => 'Audit Test User',
            'email' => 'audituser@sikomando.test',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'module' => 'user_management',
        ]);
    }
}
