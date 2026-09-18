<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InternalAuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $auditor;

    private User $pemohon;

    private AuditLog $auditLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->auditor = User::factory()->create(['is_active' => true]);
        $this->auditor->roles()->attach(Role::where('code', 'AUDITOR')->first());

        $this->pemohon = User::factory()->create(['is_active' => true]);
        $this->pemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->auditLog = AuditLog::create([
            'id' => (string) Str::uuid(),
            'actor_id' => $this->superAdmin->id,
            'action' => 'user.created',
            'module' => 'user_management',
            'entity_type' => User::class,
            'entity_id' => (string) Str::uuid(),
            'request_id' => 'req-audit-test-01',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'active'],
            'metadata' => ['context' => 'test'],
            'occurred_at' => now(),
        ]);
    }

    public function test_super_admin_can_list_internal_audit_logs(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/internal/audit-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.action', 'user.created')
            ->assertJsonPath('data.data.0.module', 'user_management');
    }

    public function test_auditor_can_list_internal_audit_logs(): void
    {
        Sanctum::actingAs($this->auditor);

        $response = $this->getJson('/api/v1/internal/audit-logs');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_pemohon_cannot_access_internal_audit_logs(): void
    {
        Sanctum::actingAs($this->pemohon);

        $response = $this->getJson('/api/v1/internal/audit-logs');

        $response->assertForbidden();
    }

    public function test_can_filter_audit_logs_by_module_and_action(): void
    {
        Sanctum::actingAs($this->superAdmin);

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'actor_id' => $this->superAdmin->id,
            'action' => 'disbursement.paid',
            'module' => 'disbursement',
            'entity_type' => 'App\\Models\\Disbursement',
            'entity_id' => (string) Str::uuid(),
            'occurred_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/internal/audit-logs?module=disbursement');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.module', 'disbursement');
    }

    public function test_super_admin_can_view_specific_audit_log_detail(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson("/api/v1/internal/audit-logs/{$this->auditLog->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->auditLog->id)
            ->assertJsonPath('data.request_id', 'req-audit-test-01')
            ->assertJsonPath('data.new_values.status', 'active');
    }
}
