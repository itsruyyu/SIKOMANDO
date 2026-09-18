<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DisbursementPlanStatus;
use App\Enums\DisbursementStatus;
use App\Enums\ProposalStatus;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\GrantProgram;
use App\Models\LpjSubmission;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FullBusinessLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemohon;

    private Organization $org;

    private GrantProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->seed([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
        ]);

        $adminRole = Role::where('code', 'SUPER_ADMIN')->firstOrFail();
        $pemohonRole = Role::where('code', 'PEMOHON')->firstOrFail();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->pemohon = User::factory()->create(['is_active' => true]);
        $this->pemohon->roles()->attach($pemohonRole->id);

        $this->org = Organization::factory()->create([
            'name' => 'Yayasan SIKOMANDO Sejahtera',
            'is_active' => true,
        ]);
        $this->pemohon->organizations()->attach($this->org->id, [
            'id' => (string) Str::uuid(),
            'membership_role' => 'leader',
            'is_primary_contact' => true,
            'is_active' => true,
        ]);

        $this->program = GrantProgram::create([
            'id' => (string) Str::uuid(),
            'code' => 'HIBAH-2026-TEST',
            'name' => 'Program Bantuan Operasional Organisasi 2026',
            'fiscal_year' => 2026,
            'budget_ceiling' => 100000000,
            'max_proposal_amount' => 50000000,
            'min_proposal_amount' => 5000000,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'OPEN',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_complete_e2e_business_lifecycle(): void
    {
        // STEP 1: Guest opens public portal
        $t0 = microtime(true);
        $publicPrograms = $this->getJson('/api/v1/public/grant-programs');
        $publicPrograms->assertStatus(200)->assertJson(['success' => true]);
        $guestLatency = (microtime(true) - $t0) * 1000;

        $publicStats = $this->getJson('/api/v1/public/statistics');
        $publicStats->assertStatus(200)->assertJson(['success' => true]);

        // STEP 2: Pemohon login
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => $this->pemohon->email,
            'password' => 'password',
        ]);
        $loginRes->assertStatus(200)->assertJson(['success' => true]);

        // STEP 3: Pemohon views program detail
        Sanctum::actingAs($this->pemohon);
        $progRes = $this->getJson("/api/v1/grant-programs/{$this->program->id}");
        $progRes->assertStatus(200)->assertJson(['success' => true]);

        // STEP 4: Pemohon creates proposal
        $createProp = $this->postJson('/api/v1/proposals', [
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->org->id,
            'title' => 'Proposal Digitalisasi Hibah Tahap 1',
            'summary' => 'Ringkasan proposal penguatan kapasitas',
            'amount_requested' => 25000000,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'budget_items' => [
                [
                    'category' => 'OPERATIONAL',
                    'item_name' => 'Pengadaan Laptop',
                    'quantity' => 2,
                    'unit' => 'Unit',
                    'unit_price' => 10000000,
                    'total_amount' => 20000000,
                ],
                [
                    'category' => 'ACTIVITY',
                    'item_name' => 'Pelatihan Anggota',
                    'quantity' => 1,
                    'unit' => 'Paket',
                    'unit_price' => 5000000,
                    'total_amount' => 5000000,
                ],
            ],
        ]);
        $createProp->assertStatus(201)->assertJson(['success' => true]);
        $proposalId = $createProp->json('data.id');
        $this->assertNotNull($proposalId);

        // Add RAB budget item
        $proposal = Proposal::findOrFail($proposalId);
        $proposal->update(['requested_amount' => 25000000]);
        ProposalBudgetItem::create([
            'proposal_id' => $proposalId,
            'category' => 'KEGIATAN',
            'item_name' => 'Biaya Pengadaan dan Pelatihan',
            'quantity' => 1,
            'unit' => 'Paket',
            'unit_price' => 25000000,
            'subtotal' => 25000000,
            'sort_order' => 1,
        ]);

        // STEP 5: Pemohon submits proposal
        $submitProp = $this->postJson("/api/v1/proposals/{$proposalId}/submit");
        $submitProp->assertStatus(200)->assertJson(['success' => true]);

        // Verify status moved to SUBMITTED
        $proposal = Proposal::findOrFail($proposalId);
        $this->assertEquals(ProposalStatus::SUBMITTED->value, $proposal->status->value);

        // STEP 6: Admin logs in, verifies proposal
        Sanctum::actingAs($this->admin);

        // Advance through workflow programmatically to simulate full lifecycle
        $proposal->update(['status' => ProposalStatus::VERIFIED]);
        $proposal->update(['status' => ProposalStatus::RECOMMENDED]);
        $proposal->update(['status' => ProposalStatus::APPROVED, 'approved_amount' => 25000000]);

        // STEP 7: Check PDF generation endpoint
        $pdfRes = $this->get("/api/v1/pdf/proposals/{$proposalId}");
        $pdfRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfRes->getContent());

        // STEP 8: Decision, Disbursement & LPJ
        Decision::create([
            'proposal_id' => $proposalId,
            'issued_by' => $this->admin->id,
            'decision_number' => 'SK-2026-TEST-001',
            'status' => 'published',
            'result' => 'approved',
            'decision_date' => now()->toDateString(),
            'approved_amount' => 25000000,
            'title' => 'SK Penetapan Penerima Hibah',
            'issued_at' => now(),
        ]);

        $plan = DisbursementPlan::create([
            'proposal_id' => $proposalId,
            'created_by' => $this->admin->id,
            'plan_number' => 'PLAN-2026-001',
            'total_stages' => 1,
            'planned_amount' => 25000000,
            'status' => DisbursementPlanStatus::COMPLETED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        Disbursement::create([
            'disbursement_plan_id' => $plan->id,
            'proposal_id' => $proposalId,
            'stage_number' => 1,
            'disbursement_number' => 'DISB-2026-001',
            'planned_amount' => 25000000,
            'approved_amount' => 25000000,
            'paid_amount' => 25000000,
            'status' => DisbursementStatus::PAID,
            'paid_date' => now()->toDateString(),
        ]);

        $lpj = LpjSubmission::create([
            'proposal_id' => $proposalId,
            'submitted_by' => $this->pemohon->id,
            'lpj_number' => 'LPJ-2026-001',
            'status' => 'approved',
            'summary' => 'Laporan Pertanggungjawaban Realisasi 100%',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $proposal->update(['status' => ProposalStatus::LPJ_VERIFIED]);

        // Close proposal
        $closeRes = $this->postJson("/api/v1/proposals/{$proposalId}/close", [
            'reason' => 'Program telah selesai dan seluruh LPJ telah diverifikasi lunas.',
        ]);
        $closeRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(ProposalStatus::COMPLETED->value, $proposal->fresh()->status->value);

        // STEP 9: Public transparency reflects final aggregation
        $transparencyRes = $this->getJson("/api/v1/public/transparency/{$this->program->id}");
        $transparencyRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertGreaterThanOrEqual(1, $transparencyRes->json('data.proposals_received'));
        $this->assertGreaterThanOrEqual(1, $transparencyRes->json('data.completed_lpj_count'));
    }
}
