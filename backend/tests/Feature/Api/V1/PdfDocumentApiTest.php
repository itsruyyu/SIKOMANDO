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

class PdfDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemohon;

    private User $otherPemohon;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::where('code', 'SUPER_ADMIN')->first());

        $this->pemohon = User::factory()->create(['is_active' => true]);
        $this->pemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $this->otherPemohon = User::factory()->create(['is_active' => true]);
        $this->otherPemohon->roles()->attach(Role::where('code', 'PEMOHON')->first());

        $program = GrantProgram::factory()->create(['is_active' => true]);
        $org = Organization::factory()->create();

        $this->pemohon->organizations()->attach($org->id, [
            'id' => (string) Str::uuid(),
            'membership_role' => 'KETUA',
            'is_primary_contact' => true,
            'is_active' => true,
        ]);

        $this->proposal = Proposal::create([
            'id' => (string) Str::uuid(),
            'grant_program_id' => $program->id,
            'organization_id' => $org->id,
            'applicant_id' => $this->pemohon->id,
            'proposal_number' => 'PROP-PDF-001',
            'title' => 'Proposal Uji Cetak PDF',
            'requested_amount' => 50000000,
            'status' => ProposalStatus::SUBMITTED,
        ]);
    }

    public function test_admin_can_generate_proposal_summary_pdf(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->get("/api/v1/pdf/proposals/{$this->proposal->id}");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pemohon_can_generate_own_proposal_summary_pdf(): void
    {
        Sanctum::actingAs($this->pemohon);

        $response = $this->get("/api/v1/pdf/proposals/{$this->proposal->id}");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_other_pemohon_cannot_generate_proposal_summary_pdf(): void
    {
        Sanctum::actingAs($this->otherPemohon);

        $response = $this->getJson("/api/v1/pdf/proposals/{$this->proposal->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_pdf(): void
    {
        $response = $this->getJson("/api/v1/pdf/proposals/{$this->proposal->id}");

        $response->assertUnauthorized();
    }

    public function test_pdf_generation_records_audit_trail(): void
    {
        Sanctum::actingAs($this->admin);

        $this->get("/api/v1/pdf/proposals/{$this->proposal->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pdf.proposal_generated',
            'module' => 'pdf',
        ]);
    }
}
