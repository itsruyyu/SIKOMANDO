<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DecisionDocumentStatus;
use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\Disbursement;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DisbursementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $approver1;

    private User $approver2;

    private User $verifikator;

    private User $auditor;

    private User $pemohon;

    private User $surveyor;

    private GrantProgram $grantProgram;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $superAdminRole = Role::query()->firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Admin', 'is_system' => true, 'is_active' => true]
        );

        $approverRole = Role::query()->firstOrCreate(
            ['code' => 'APPROVER'],
            ['id' => (string) Str::uuid(), 'name' => 'Approver', 'is_system' => false, 'is_active' => true]
        );

        $verifikatorRole = Role::query()->firstOrCreate(
            ['code' => 'VERIFIKATOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Verifikator', 'is_system' => false, 'is_active' => true]
        );

        $auditorRole = Role::query()->firstOrCreate(
            ['code' => 'AUDITOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Auditor', 'is_system' => false, 'is_active' => true]
        );

        $pemohonRole = Role::query()->firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => false, 'is_active' => true]
        );

        $surveyorRole = Role::query()->firstOrCreate(
            ['code' => 'SURVEYOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Surveyor', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create(['name' => 'Admin Sikomando']);
        $this->admin->roles()->sync([$superAdminRole->id]);

        $this->approver1 = User::factory()->create(['name' => 'Kepala Dinas 1']);
        $this->approver1->roles()->sync([$approverRole->id]);

        $this->approver2 = User::factory()->create(['name' => 'Kepala Dinas 2']);
        $this->approver2->roles()->sync([$approverRole->id]);

        $this->verifikator = User::factory()->create(['name' => 'Tim Verifikasi']);
        $this->verifikator->roles()->sync([$verifikatorRole->id]);

        $this->auditor = User::factory()->create(['name' => 'Auditor Inspektorat']);
        $this->auditor->roles()->sync([$auditorRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Kelompok Tani']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->surveyor = User::factory()->create(['name' => 'Petugas Lapangan']);
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->grantProgram = GrantProgram::factory()->create([
            'name' => 'Program Hibah Pertanian 2026',
            'is_active' => true,
        ]);
    }

    public function test_proposal_without_approved_decision_cannot_have_disbursement_plan_created(): void
    {
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::RECOMMENDED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_proposal_without_active_sk_document_cannot_have_disbursement_plan_created(): void
    {
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::APPROVED,
            'approved_amount' => 50000000,
        ]);

        // Decision exists but has no active decision_letter SK document
        Decision::create([
            'proposal_id' => $proposal->id,
            'issued_by' => $this->approver1->id,
            'decision_number' => 'SK-2026-001',
            'status' => DecisionStatus::PUBLISHED,
            'result' => DecisionResult::APPROVED,
            'decision_date' => now()->toDateString(),
            'approved_amount' => 50000000,
            'title' => 'Keputusan Bupati',
            'issued_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_planned_amount_exceeding_approved_ceiling_is_rejected(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 60000000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['planned_amount']);
    }

    public function test_can_create_single_stage_disbursement_plan(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
            'planned_date' => now()->addDays(5)->toDateString(),
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1400012345678',
            'bank_account_name' => 'Kelompok Tani Subur Makmur',
            'notes' => 'Pencairan 100% setelah penetapan SK.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_stages', 1)
            ->assertJsonPath('data.planned_amount', 50000000)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('disbursement_plans', [
            'proposal_id' => $proposal->id,
            'planned_amount' => 50000000,
            'total_stages' => 1,
        ]);

        $this->assertDatabaseHas('disbursements', [
            'proposal_id' => $proposal->id,
            'stage_number' => 1,
            'planned_amount' => 50000000,
            'status' => 'pending',
            'bank_name' => 'Bank Mandiri',
        ]);
    }

    public function test_can_create_multi_stage_disbursement_plan(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'notes' => 'Rencana pencairan bertahap 60% dan 40%.',
            'stages' => [
                [
                    'stage_number' => 1,
                    'planned_amount' => 30000000,
                    'planned_date' => now()->addDays(3)->toDateString(),
                    'bank_name' => 'Bank Mandiri',
                    'bank_account_number' => '1400012345678',
                    'bank_account_name' => 'Kelompok Tani Subur Makmur',
                    'notes' => 'Tahap 1: Modal awal pengadaan bibit.',
                ],
                [
                    'stage_number' => 2,
                    'planned_amount' => 20000000,
                    'planned_date' => now()->addDays(30)->toDateString(),
                    'bank_name' => 'Bank Mandiri',
                    'bank_account_number' => '1400012345678',
                    'bank_account_name' => 'Kelompok Tani Subur Makmur',
                    'notes' => 'Tahap 2: Pengadaan pupuk setelah verifikasi lapangan.',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_stages', 2)
            ->assertJsonPath('data.planned_amount', 50000000);

        $this->assertDatabaseCount('disbursements', 2);
    }

    public function test_unauthorized_actor_cannot_create_or_manage_disbursement(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        // Pemohon cannot create plan
        Sanctum::actingAs($this->pemohon);
        $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ])->assertForbidden();

        // Surveyor cannot create plan
        Sanctum::actingAs($this->surveyor);
        $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ])->assertForbidden();
    }

    public function test_disbursement_verification_lifecycle(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $disbursementId = $planRes->json('data.disbursements.0.id');

        // Verifikator verifies disbursement
        Sanctum::actingAs($this->verifikator);
        $verifyRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/verify", [
            'bank_name' => 'Bank BRI',
            'bank_account_number' => '001122334455',
            'bank_account_name' => 'Kelompok Tani Subur Makmur',
            'notes' => 'Rekening bank telah dicocokkan dengan buku tabungan fisik dan aktif.',
        ]);

        $verifyRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.bank_name', 'Bank BRI');

        $this->assertDatabaseHas('disbursements', [
            'id' => $disbursementId,
            'status' => 'verified',
            'bank_name' => 'Bank BRI',
        ]);
    }

    public function test_maker_checker_rule_creator_cannot_approve_own_disbursement(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        // Approver 1 creates the plan (Maker)
        Sanctum::actingAs($this->approver1);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $disbursementId = $planRes->json('data.disbursements.0.id');

        // Maker attempts to approve own plan -> 422 Maker-Checker violation
        $attemptRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/approve", [
            'approved_amount' => 50000000,
            'notes' => 'Menyetujui diri sendiri.',
        ]);

        $attemptRes->assertStatus(422)
            ->assertJsonValidationErrors(['actor']);

        // Different Approver (Approver 2) approves -> Succeeds!
        Sanctum::actingAs($this->approver2);
        $approveRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/approve", [
            'approved_amount' => 50000000,
            'notes' => 'Disetujui untuk diproses ke BPKAD.',
        ]);

        $approveRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('disbursements', [
            'id' => $disbursementId,
            'status' => 'approved',
            'approved_amount' => 50000000,
        ]);
    }

    public function test_cannot_record_transaction_before_disbursement_is_approved(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $disbursementId = $planRes->json('data.disbursements.0.id');

        // Disbursement is currently 'pending'
        $txnRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/transactions", [
            'amount' => 50000000,
            'bank_reference' => 'TXN-BANK-001',
        ]);

        $txnRes->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_recording_transaction_updates_disbursement_to_paid_and_transitions_proposal_to_disbursed(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->approver1);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $disbursementId = $planRes->json('data.disbursements.0.id');
        $planId = $planRes->json('data.id');

        // Approver 2 approves
        Sanctum::actingAs($this->approver2);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/approve", [
            'approved_amount' => 50000000,
        ])->assertOk();

        // Admin records the bank transfer transaction
        Sanctum::actingAs($this->admin);
        $txnRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/transactions", [
            'amount' => 50000000,
            'transaction_type' => 'sp2d_transfer',
            'transaction_date' => now()->toDateString(),
            'bank_reference' => 'SP2D-2026-9999',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1400012345678',
            'recipient_name' => 'Kelompok Tani Subur Makmur',
            'notes' => 'Pencairan SP2D telah ditransfer langsung ke rekening kas organisasi.',
        ]);

        $txnRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 50000000)
            ->assertJsonPath('data.bank_reference', 'SP2D-2026-9999')
            ->assertJsonPath('data.status', 'confirmed');

        // Check disbursement is PAID
        $this->assertDatabaseHas('disbursements', [
            'id' => $disbursementId,
            'status' => 'paid',
            'paid_amount' => 50000000,
        ]);

        // Check proposal transitioned to IMPLEMENTATION (BE-14: auto transition after all stages paid)
        $this->assertEquals(ProposalStatus::IMPLEMENTATION, $proposal->fresh()->status);

        // Check plan is COMPLETED because all stages are paid
        $this->assertDatabaseHas('disbursement_plans', [
            'id' => $planId,
            'status' => 'completed',
        ]);
    }

    public function test_cumulative_paid_amount_cannot_exceed_approved_ceiling(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'stages' => [
                ['stage_number' => 1, 'planned_amount' => 30000000],
                ['stage_number' => 2, 'planned_amount' => 20000000],
            ],
        ]);

        $stage1Id = $planRes->json('data.disbursements.0.id');
        $stage2Id = $planRes->json('data.disbursements.1.id');

        // Approver approves stage 1 and stage 2
        Sanctum::actingAs($this->approver1);
        $this->postJson("/api/v1/disbursements/{$stage1Id}/approve", ['approved_amount' => 30000000])->assertOk();
        $this->postJson("/api/v1/disbursements/{$stage2Id}/approve", ['approved_amount' => 20000000])->assertOk();

        // Stage 1 paid 30,000,000
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/disbursements/{$stage1Id}/transactions", [
            'amount' => 30000000,
            'bank_reference' => 'SP2D-STAGE-1',
        ])->assertCreated();

        // Stage 2 tries to record 25,000,000 (which would exceed 20,000,000 approved and 50,000,000 total)
        $invalidStage2 = $this->postJson("/api/v1/disbursements/{$stage2Id}/transactions", [
            'amount' => 25000000,
            'bank_reference' => 'SP2D-STAGE-2',
        ]);

        $invalidStage2->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_duplicate_bank_reference_is_rejected(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'stages' => [
                ['stage_number' => 1, 'planned_amount' => 25000000],
                ['stage_number' => 2, 'planned_amount' => 25000000],
            ],
        ]);

        $stage1Id = $planRes->json('data.disbursements.0.id');
        $stage2Id = $planRes->json('data.disbursements.1.id');

        Sanctum::actingAs($this->approver1);
        $this->postJson("/api/v1/disbursements/{$stage1Id}/approve", ['approved_amount' => 25000000]);
        $this->postJson("/api/v1/disbursements/{$stage2Id}/approve", ['approved_amount' => 25000000]);

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/disbursements/{$stage1Id}/transactions", [
            'amount' => 25000000,
            'bank_reference' => 'REF-BANK-SAME',
        ])->assertCreated();

        // Reuse identical bank_reference
        $dupRes = $this->postJson("/api/v1/disbursements/{$stage2Id}/transactions", [
            'amount' => 25000000,
            'bank_reference' => 'REF-BANK-SAME',
        ]);

        $dupRes->assertStatus(422)
            ->assertJsonValidationErrors(['bank_reference']);
    }

    public function test_final_disbursement_cannot_be_re_verified_or_re_approved(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->approver1);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $disbursementId = $planRes->json('data.disbursements.0.id');

        Sanctum::actingAs($this->approver2);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/approve")->assertOk();

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/transactions", [
            'amount' => 50000000,
            'bank_reference' => 'REF-FINAL-TEST',
        ])->assertCreated();

        // Attempting to re-verify a paid disbursement -> 422
        Sanctum::actingAs($this->verifikator);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/verify")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Attempting to re-approve a paid disbursement -> 422
        Sanctum::actingAs($this->approver2);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_pemohon_can_only_view_own_disbursements_and_summary(): void
    {
        $myProposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000, applicant: $this->pemohon);

        $otherApplicant = User::factory()->create();
        $otherProposal = $this->createApprovedProposalWithSK(approvedAmount: 40000000, applicant: $otherApplicant);

        Sanctum::actingAs($this->admin);
        $myPlan = $this->postJson("/api/v1/proposals/{$myProposal->id}/disbursement-plans", ['planned_amount' => 50000000]);
        $myDisbId = $myPlan->json('data.disbursements.0.id');

        $otherPlan = $this->postJson("/api/v1/proposals/{$otherProposal->id}/disbursement-plans", ['planned_amount' => 40000000]);
        $otherDisbId = $otherPlan->json('data.disbursements.0.id');

        // Pemohon tests
        Sanctum::actingAs($this->pemohon);

        // General list is forbidden
        $this->getJson('/api/v1/disbursements')->assertForbidden();

        // Can view own proposal disbursements
        $this->getJson("/api/v1/proposals/{$myProposal->id}/disbursements")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Can view own proposal disbursement summary
        $this->getJson("/api/v1/proposals/{$myProposal->id}/disbursement-summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.approved_ceiling', 50000000);

        // Can view own disbursement detail
        $this->getJson("/api/v1/disbursements/{$myDisbId}")
            ->assertOk()
            ->assertJsonPath('data.id', $myDisbId);

        // Forbidden on other applicant's proposal
        $this->getJson("/api/v1/proposals/{$otherProposal->id}/disbursements")
            ->assertForbidden();
        $this->getJson("/api/v1/proposals/{$otherProposal->id}/disbursement-summary")
            ->assertForbidden();
        $this->getJson("/api/v1/disbursements/{$otherDisbId}")
            ->assertForbidden();
    }

    public function test_auditor_has_read_only_access(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->admin);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", ['planned_amount' => 50000000]);
        $disbursementId = $planRes->json('data.disbursements.0.id');

        // Auditor
        Sanctum::actingAs($this->auditor);

        // Read access allowed
        $this->getJson('/api/v1/disbursements')->assertOk();
        $this->getJson("/api/v1/disbursements/{$disbursementId}")->assertOk();
        $this->getJson("/api/v1/proposals/{$proposal->id}/disbursements")->assertOk();
        $this->getJson("/api/v1/proposals/{$proposal->id}/disbursement-summary")->assertOk();

        // Mutation operations forbidden
        $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", ['planned_amount' => 50000000])
            ->assertForbidden();
        $this->postJson("/api/v1/disbursements/{$disbursementId}/verify")
            ->assertForbidden();
        $this->postJson("/api/v1/disbursements/{$disbursementId}/approve")
            ->assertForbidden();
        $this->postJson("/api/v1/disbursements/{$disbursementId}/transactions", ['amount' => 50000000])
            ->assertForbidden();
    }

    public function test_audit_trail_recorded_across_disbursement_lifecycle(): void
    {
        $proposal = $this->createApprovedProposalWithSK(approvedAmount: 50000000);

        Sanctum::actingAs($this->approver1);
        $planRes = $this->postJson("/api/v1/proposals/{$proposal->id}/disbursement-plans", [
            'planned_amount' => 50000000,
        ]);
        $planId = $planRes->json('data.id');
        $disbursementId = $planRes->json('data.disbursements.0.id');

        Sanctum::actingAs($this->verifikator);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/verify")->assertOk();

        Sanctum::actingAs($this->approver2);
        $this->postJson("/api/v1/disbursements/{$disbursementId}/approve")->assertOk();

        Sanctum::actingAs($this->admin);
        $txnRes = $this->postJson("/api/v1/disbursements/{$disbursementId}/transactions", [
            'amount' => 50000000,
            'bank_reference' => 'AUDIT-REF-001',
        ])->assertCreated();
        $transactionId = $txnRes->json('data.id');

        // Assert audit trail
        $this->assertTrue(AuditLog::where('action', 'disbursement_plan.created')->where('entity_id', $planId)->exists());
        $this->assertTrue(AuditLog::where('action', 'disbursement.verified')->where('entity_id', $disbursementId)->exists());
        $this->assertTrue(AuditLog::where('action', 'disbursement.approved')->where('entity_id', $disbursementId)->exists());
        $this->assertTrue(AuditLog::where('action', 'disbursement.paid')->where('entity_id', $disbursementId)->exists());
        $this->assertTrue(AuditLog::where('action', 'disbursement.transaction_recorded')->where('entity_id', $transactionId)->exists());
    }

    private function createApprovedProposalWithSK(float $approvedAmount = 50000000, ?User $applicant = null): Proposal
    {
        $applicant = $applicant ?: $this->pemohon;

        $org = Organization::factory()->create([
            'name' => 'Kelompok Tani Subur Makmur',
        ]);

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'applicant_id' => $applicant->id,
            'organization_id' => $org->id,
            'title' => 'Proposal Budidaya Padi Organik',
            'status' => ProposalStatus::APPROVED,
            'requested_amount' => $approvedAmount,
            'approved_amount' => $approvedAmount,
            'approved_at' => now(),
        ]);

        $decision = Decision::create([
            'proposal_id' => $proposal->id,
            'issued_by' => $this->approver1->id,
            'decision_number' => 'SK-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'decision_type' => 'grant_award',
            'status' => DecisionStatus::PUBLISHED,
            'result' => DecisionResult::APPROVED,
            'decision_date' => now()->toDateString(),
            'approved_amount' => $approvedAmount,
            'title' => 'Surat Keputusan Bupati tentang Penerima Hibah',
            'issued_at' => now(),
        ]);

        DecisionDocument::create([
            'decision_id' => $decision->id,
            'uploaded_by' => $this->approver1->id,
            'document_type' => 'decision_letter',
            'document_title' => 'Naskah SK Bupati Terbit',
            'original_filename' => 'sk_bupati.pdf',
            'stored_filename' => 'sk_bupati_stored.pdf',
            'disk' => 'private',
            'storage_path' => 'decisions/documents/sk_bupati_stored.pdf',
            'status' => DecisionDocumentStatus::ACTIVE,
            'version' => 1,
            'generated_at' => now(),
        ]);

        return $proposal;
    }
}
