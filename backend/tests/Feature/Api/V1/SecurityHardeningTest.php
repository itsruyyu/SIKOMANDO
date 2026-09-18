<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProposalStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $activeUser;

    private User $inactiveUser;

    private User $pemohonA;

    private User $pemohonB;

    private Proposal $proposalA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->activeUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->activeUser->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->inactiveUser = User::factory()->create([
            'is_active' => false,
        ]);
        $this->inactiveUser->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->pemohonA = User::factory()->create(['is_active' => true]);
        $this->pemohonA->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->pemohonB = User::factory()->create(['is_active' => true]);
        $this->pemohonB->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $program = GrantProgram::factory()->create(['is_active' => true]);
        $orgA = Organization::factory()->create();

        $this->proposalA = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $program->id,
            'organization_id' => $orgA->id,
            'applicant_id' => $this->pemohonA->id,
            'proposal_number' => 'PROP-SEC-001',
            'title' => 'Proposal Rahasia Organisasi A',
            'requested_amount' => 25000000,
            'status' => ProposalStatus::SUBMITTED,
        ]);
    }

    public function test_inactive_user_token_is_rejected_by_active_user_middleware(): void
    {
        Sanctum::actingAs($this->inactiveUser);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akun pengguna dinonaktifkan. Silakan hubungi administrator.');
    }

    public function test_api_response_includes_request_id_header(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Request-ID');

        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_api_accepts_and_propagates_custom_request_id(): void
    {
        $customId = 'trace-test-request-id-12345';

        $response = $this->withHeaders([
            'X-Request-ID' => $customId,
        ])->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Request-ID', $customId);
    }

    public function test_security_headers_are_present_on_api_responses(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_applicant_cannot_view_another_applicants_proposal_detail_idor_protection(): void
    {
        Sanctum::actingAs($this->pemohonB);

        $response = $this->getJson("/api/v1/proposals/{$this->proposalA->id}");

        $response->assertForbidden();
    }

    public function test_audit_logs_cannot_be_deleted_via_api(): void
    {
        Sanctum::actingAs($this->activeUser);

        // Any attempt to delete audit logs returns method not allowed or not found (immutable)
        $response = $this->deleteJson('/api/v1/internal/audit-logs/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(405);
    }
}
