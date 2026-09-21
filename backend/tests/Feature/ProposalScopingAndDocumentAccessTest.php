<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Enums\ProposalStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\DocumentType;
use App\Models\Proposal;
use App\Models\ProposalAssignment;
use App\Models\ProposalDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProposalScopingAndDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Role $pemohonRole;
    protected Role $verifikatorRole;
    protected Role $approverRole;
    protected Role $adminRole;
    protected GrantProgram $program;
    protected Organization $organization;
    protected User $defaultApplicant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemohonRole = Role::firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => true, 'is_active' => true]
        );

        $this->verifikatorRole = Role::firstOrCreate(
            ['code' => 'VERIFIKATOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Verifikator', 'is_system' => true, 'is_active' => true]
        );

        $this->approverRole = Role::firstOrCreate(
            ['code' => 'APPROVER'],
            ['id' => (string) Str::uuid(), 'name' => 'Approver', 'is_system' => true, 'is_active' => true]
        );

        $this->adminRole = Role::firstOrCreate(
            ['code' => 'ADMIN_SIKOMANDO'],
            ['id' => (string) Str::uuid(), 'name' => 'Admin SIKOMANDO', 'is_system' => true, 'is_active' => true]
        );

        $this->program = GrantProgram::create([
            'id' => (string) Str::uuid(),
            'name' => 'Bantuan Sosial 2026',
            'code' => 'BANSOS-2026',
            'fiscal_year' => 2026,
            'total_budget' => 500000000,
            'available_budget' => 500000000,
            'is_active' => true,
        ]);

        $this->organization = Organization::create([
            'id' => (string) Str::uuid(),
            'code' => 'ORG-001',
            'name' => 'Yayasan Sejahtera Bersama',
            'organization_type' => 'COMMUNITY',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $this->defaultApplicant = User::factory()->create(['is_active' => true]);
        $this->defaultApplicant->roles()->attach($this->pemohonRole->id);
    }

    public function test_applicant_can_only_see_their_own_proposals(): void
    {
        $applicantA = User::factory()->create(['is_active' => true]);
        $applicantA->roles()->attach($this->pemohonRole->id);

        $applicantB = User::factory()->create(['is_active' => true]);
        $applicantB->roles()->attach($this->pemohonRole->id);

        $proposalA = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-A-001',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $applicantA->id,
            'title' => 'Proposal Milik Pemohon A',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::DRAFT,
            'created_by' => $applicantA->id,
        ]);

        $proposalB = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-B-001',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $applicantB->id,
            'title' => 'Proposal Milik Pemohon B',
            'requested_amount' => 75000000,
            'status' => ProposalStatus::DRAFT,
            'created_by' => $applicantB->id,
        ]);

        Sanctum::actingAs($applicantA);

        // Index listing should only show Proposal A
        $response = $this->getJson('/api/v1/proposals');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $proposalA->id]);
        $response->assertJsonMissing(['id' => $proposalB->id]);

        // Attempting to view Proposal B detail directly must be 403 Forbidden
        $detailResponse = $this->getJson("/api/v1/proposals/{$proposalB->id}");
        $detailResponse->assertForbidden();
    }

    public function test_verifikator_can_only_see_assigned_proposals(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($this->adminRole->id);

        $verifikator1 = User::factory()->create(['is_active' => true]);
        $verifikator1->roles()->attach($this->verifikatorRole->id);

        $verifikator2 = User::factory()->create(['is_active' => true]);
        $verifikator2->roles()->attach($this->verifikatorRole->id);

        $proposal1 = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-VERIF-1',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'Proposal Untuk Verifikator 1',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        $proposal2 = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-VERIF-2',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'Proposal Untuk Verifikator 2',
            'requested_amount' => 60000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        // Admin assigns proposal 1 to verifikator 1
        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $proposal1->id,
            'assigned_user_id' => $verifikator1->id,
            'assigned_by' => $admin->id,
            'assignment_type' => AssignmentType::VERIFICATION->value,
            'status' => AssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
        ]);

        // Admin assigns proposal 2 to verifikator 2
        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $proposal2->id,
            'assigned_user_id' => $verifikator2->id,
            'assigned_by' => $admin->id,
            'assignment_type' => AssignmentType::VERIFICATION->value,
            'status' => AssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($verifikator1);

        // Verifikator 1 index should only show proposal 1
        $response = $this->getJson('/api/v1/proposals');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $proposal1->id]);
        $response->assertJsonMissing(['id' => $proposal2->id]);

        // Direct detail view of proposal 2 should be 403 Forbidden
        $detailResponse = $this->getJson("/api/v1/proposals/{$proposal2->id}");
        $detailResponse->assertForbidden();
    }

    public function test_document_access_is_strictly_scoped(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($this->adminRole->id);

        $verifikator1 = User::factory()->create(['is_active' => true]);
        $verifikator1->roles()->attach($this->verifikatorRole->id);

        $verifikator2 = User::factory()->create(['is_active' => true]);
        $verifikator2->roles()->attach($this->verifikatorRole->id);

        $proposal = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-DOC-001',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'Proposal Dokumen Uji',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        // Assign to verifikator 1 only
        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $proposal->id,
            'assigned_user_id' => $verifikator1->id,
            'assigned_by' => $admin->id,
            'assignment_type' => AssignmentType::VERIFICATION->value,
            'status' => AssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
        ]);

        $docType = DocumentType::firstOrCreate(
            ['code' => 'LEGAL_DOC'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dokumen Legalitas',
                'scope' => 'proposal',
                'allowed_mime_types' => ['application/pdf'],
                'max_size_kb' => 5120,
                'is_active' => true,
            ]
        );

        $doc = ProposalDocument::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $proposal->id,
            'document_type_id' => $docType->id,
            'original_filename' => 'akta.pdf',
            'stored_filename' => 'akta_hash.pdf',
            'storage_disk' => 'public',
            'storage_path' => 'proposals/akta.pdf',
            'file_size' => 1024,
            'file_hash' => hash('sha256', 'dummy'),
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_by' => $proposal->applicant_id,
        ]);

        // Verifikator 1 can list documents
        Sanctum::actingAs($verifikator1);
        $res1 = $this->getJson("/api/v1/proposals/{$proposal->id}/documents");
        $res1->assertOk();

        // Verifikator 2 cannot list documents (403)
        Sanctum::actingAs($verifikator2);
        $res2 = $this->getJson("/api/v1/proposals/{$proposal->id}/documents");
        $res2->assertForbidden();

        // Verifikator 2 cannot view specific document (403)
        $resDoc = $this->getJson("/api/v1/proposals/{$proposal->id}/documents/{$doc->id}");
        $resDoc->assertForbidden();
    }

    public function test_approver_only_sees_proposals_in_approval_or_higher_stages(): void
    {
        $approver = User::factory()->create(['is_active' => true]);
        $approver->roles()->attach($this->approverRole->id);

        $draftProp = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-DRAFT',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'Proposal Masih Draft',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::DRAFT,
        ]);

        $approvedProp = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'PROP-RECOMMENDED',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'Proposal Tahap Rekomendasi',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::RECOMMENDED,
        ]);

        Sanctum::actingAs($approver);

        $response = $this->getJson('/api/v1/proposals');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $approvedProp->id]);
        $response->assertJsonMissing(['id' => $draftProp->id]);
    }

    public function test_my_workload_endpoint_returns_accurate_assignment_counts(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($this->adminRole->id);

        $verifikator = User::factory()->create(['is_active' => true]);
        $verifikator->roles()->attach($this->verifikatorRole->id);

        // Create 2 assignments: 1 assigned, 1 in_progress
        $prop1 = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'P1',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'P1',
            'requested_amount' => 10000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        $prop2 = Proposal::create([
            'id' => (string) Str::uuid(),
            'proposal_number' => 'P2',
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->defaultApplicant->id,
            'title' => 'P2',
            'requested_amount' => 20000000,
            'status' => ProposalStatus::VERIFICATION,
        ]);

        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $prop1->id,
            'assigned_user_id' => $verifikator->id,
            'assigned_by' => $admin->id,
            'assignment_type' => AssignmentType::VERIFICATION->value,
            'status' => AssignmentStatus::ASSIGNED->value,
            'assigned_at' => now(),
        ]);

        ProposalAssignment::create([
            'id' => (string) Str::uuid(),
            'proposal_id' => $prop2->id,
            'assigned_user_id' => $verifikator->id,
            'assigned_by' => $admin->id,
            'assignment_type' => AssignmentType::VERIFICATION->value,
            'status' => AssignmentStatus::IN_PROGRESS->value,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($verifikator);

        $response = $this->getJson('/api/v1/assignments/my-workload');
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'total' => 2,
                'assigned' => 1,
                'in_progress' => 1,
                'completed' => 0,
            ],
        ]);
    }
}
