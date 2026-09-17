<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Enums\ProposalStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $verifikator;

    private User $evaluator;

    private User $surveyor;

    private User $pemohon;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->verifikator = User::factory()->create(['name' => 'Petugas Verifikator', 'is_active' => true]);
        $this->verifikator->roles()->attach(Role::where('code', 'VERIFIKATOR')->first());

        $this->evaluator = User::factory()->create(['name' => 'Petugas Evaluator', 'is_active' => true]);
        $this->evaluator->roles()->attach(Role::where('code', 'EVALUATOR')->first());

        $this->surveyor = User::factory()->create(['name' => 'Petugas Surveyor', 'is_active' => true]);
        $this->surveyor->roles()->attach(Role::where('code', 'SURVEYOR')->first());

        $this->pemohon = User::factory()->create(['is_active' => true]);
        $this->pemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $program = GrantProgram::factory()->create(['is_active' => true]);
        $org = Organization::factory()->create();

        $this->proposal = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $program->id,
            'organization_id' => $org->id,
            'applicant_id' => $this->pemohon->id,
            'proposal_number' => 'PROP-TEST-001',
            'title' => 'Proposal Uji Penugasan',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::SUBMITTED,
        ]);
    }

    public function test_admin_can_assign_verifikator_to_proposal(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => 'VERIFICATION',
            'notes' => 'Tolong diperiksa kelengkapan berkasnya.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assignment_type', 'VERIFICATION')
            ->assertJsonPath('data.status', 'ASSIGNED')
            ->assertJsonPath('data.assigned_user.name', 'Petugas Verifikator');

        $this->assertDatabaseHas('proposal_assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => 'VERIFICATION',
            'status' => 'ASSIGNED',
        ]);
    }

    public function test_assignment_fails_if_user_lacks_required_role(): void
    {
        Sanctum::actingAs($this->admin);

        // Try to assign surveyor as evaluator
        $response = $this->postJson('/api/v1/assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->surveyor->id,
            'assignment_type' => 'EVALUATION',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_user_id']);
    }

    public function test_assignment_fails_if_user_is_inactive(): void
    {
        Sanctum::actingAs($this->admin);

        $this->verifikator->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => 'VERIFICATION',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_user_id']);
    }

    public function test_cannot_create_duplicate_active_assignment_for_same_type(): void
    {
        Sanctum::actingAs($this->admin);

        // First assignment
        $this->postJson('/api/v1/assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => 'VERIFICATION',
        ])->assertCreated();

        // Second assignment with another verifikator
        $otherVerifikator = User::factory()->create(['is_active' => true]);
        $otherVerifikator->roles()->attach(Role::where('code', 'VERIFIKATOR')->first());

        $response = $this->postJson('/api/v1/assignments', [
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $otherVerifikator->id,
            'assignment_type' => 'VERIFICATION',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assignment_type']);
    }

    public function test_admin_can_revoke_assignment_with_reason(): void
    {
        Sanctum::actingAs($this->admin);

        $assignment = ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => AssignmentType::VERIFICATION,
            'status' => AssignmentStatus::ASSIGNED,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/assignments/{$assignment->id}/revoke", [
            'reason' => 'Petugas sedang cuti sakit.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'REVOKED')
            ->assertJsonPath('data.reason', 'Petugas sedang cuti sakit.');

        $this->assertDatabaseHas('proposal_assignments', [
            'id' => $assignment->id,
            'status' => 'REVOKED',
            'reason' => 'Petugas sedang cuti sakit.',
        ]);
    }

    public function test_assigned_user_can_view_my_assignments(): void
    {
        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => AssignmentType::VERIFICATION,
            'status' => AssignmentStatus::ASSIGNED,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($this->verifikator);

        $response = $this->getJson('/api/v1/assignments/my');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_pemohon_cannot_view_assignments_or_inspector_identity(): void
    {
        Sanctum::actingAs($this->pemohon);

        // Cannot access assignment endpoints
        $this->getJson('/api/v1/assignments')->assertForbidden();
        $this->postJson('/api/v1/assignments', [])->assertForbidden();
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/assignments")->assertForbidden();
    }

    public function test_admin_can_view_workload_statistics(): void
    {
        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $this->proposal->id,
            'assigned_user_id' => $this->verifikator->id,
            'assignment_type' => AssignmentType::VERIFICATION,
            'status' => AssignmentStatus::ASSIGNED,
            'assigned_by' => $this->admin->id,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/internal/dashboard/workload');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
