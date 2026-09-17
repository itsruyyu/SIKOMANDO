<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DisbursementPlanStatus;
use App\Enums\DisbursementStatus;
use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\Disbursement;
use App\Models\DisbursementPlan;
use App\Models\GrantProgram;
use App\Models\LpjSubmission;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LpjApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $approver;

    private User $verifikator;

    private User $auditor;

    private User $pemohon;

    private User $surveyor;

    private GrantProgram $grantProgram;

    private Organization $organization;

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

        $this->approver = User::factory()->create(['name' => 'Kepala Dinas / Approver']);
        $this->approver->roles()->sync([$approverRole->id]);

        $this->verifikator = User::factory()->create(['name' => 'Tim Verifikasi']);
        $this->verifikator->roles()->sync([$verifikatorRole->id]);

        $this->auditor = User::factory()->create(['name' => 'Auditor Inspektorat']);
        $this->auditor->roles()->sync([$auditorRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Kelompok Tani']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->surveyor = User::factory()->create(['name' => 'Petugas Lapangan']);
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->grantProgram = GrantProgram::factory()->create([
            'name' => 'Program Bantuan Pertanian 2026',
            'is_active' => true,
        ]);

        $this->organization = Organization::factory()->create([
            'name' => 'Kelompok Tani Subur Makmur',
        ]);
    }

    public function test_guest_cannot_access_lpj(): void
    {
        $this->getJson('/api/v1/lpj')->assertUnauthorized();
    }

    public function test_pemohon_can_create_lpj_for_own_proposal(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Realisasi anggaran hibah untuk pengadaan bibit dan pupuk organik.',
            'items' => [
                [
                    'category' => 'Pengadaan Sarana',
                    'item_name' => 'Bibit Padi Unggul',
                    'quantity' => 10,
                    'unit' => 'paket',
                    'unit_price' => 3000000,
                    'realized_amount' => 30000000,
                    'planned_amount' => 30000000,
                ],
                [
                    'category' => 'Pengadaan Pupuk',
                    'item_name' => 'Pupuk Organik Padat',
                    'quantity' => 20,
                    'unit' => 'karung',
                    'unit_price' => 1000000,
                    'realized_amount' => 20000000,
                    'planned_amount' => 20000000,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_received', 50000000)
            ->assertJsonPath('data.total_spent', 50000000)
            ->assertJsonPath('data.remaining_balance', 0)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('lpj_submissions', [
            'proposal_id' => $proposal->id,
            'status' => 'draft',
            'total_spent' => 50000000,
        ]);
    }

    public function test_pemohon_cannot_access_or_create_lpj_for_another_pemohon_proposal(): void
    {
        $otherApplicant = User::factory()->create();
        $otherProposal = $this->createDisbursedProposal(paidAmount: 50000000, applicant: $otherApplicant);

        Sanctum::actingAs($this->pemohon);

        // Cannot create LPJ for other applicant's proposal
        $this->postJson("/api/v1/proposals/{$otherProposal->id}/lpj", [
            'summary' => 'Mencoba membuat LPJ orang lain',
        ])->assertForbidden();

        // Cannot list LPJs for other applicant's proposal
        $this->getJson("/api/v1/proposals/{$otherProposal->id}/lpj")
            ->assertForbidden();
    }

    public function test_lpj_cannot_be_created_before_disbursement(): void
    {
        // Proposal still in 'approved' status without any paid disbursements
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::APPROVED,
            'approved_amount' => 50000000,
        ]);

        Sanctum::actingAs($this->pemohon);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Mencoba membuat LPJ sebelum cair',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_total_realized_amount_exceeding_received_disbursed_amount_is_rejected(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Realisasi melebihi pagu cair',
            'items' => [
                [
                    'category' => 'Biaya Konstruksi',
                    'item_name' => 'Pembangunan Saluran Irigasi',
                    'quantity' => 1,
                    'unit' => 'unit',
                    'unit_price' => 60000000,
                    'realized_amount' => 60000000,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_spent']);
    }

    public function test_mandatory_document_and_items_validated_on_submit_lpj(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);
        $lpjRes = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Draf LPJ tanpa item dan dokumen',
        ]);
        $lpjId = $lpjRes->json('data.id');

        // Submitting with 0 items -> 422
        $submitRes = $this->postJson("/api/v1/lpj/{$lpjId}/submit");
        $submitRes->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Add item but no document
        $this->patchJson("/api/v1/lpj/{$lpjId}", [
            'items' => [
                [
                    'category' => 'Sarana',
                    'item_name' => 'Cangkul dan Sabit',
                    'quantity' => 5,
                    'unit' => 'buah',
                    'unit_price' => 100000,
                    'realized_amount' => 500000,
                ],
            ],
        ])->assertOk();

        // Submitting with items but without documents -> 422
        $submitNoDoc = $this->postJson("/api/v1/lpj/{$lpjId}/submit");
        $submitNoDoc->assertStatus(422)
            ->assertJsonValidationErrors(['documents']);
    }

    public function test_submit_lpj_successfully_transitions_lpj_to_submitted_and_proposal_to_lpj_submitted(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);
        $lpjRes = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'LPJ Lengkap dengan berkas dan item',
            'items' => [
                [
                    'category' => 'Bibit',
                    'item_name' => 'Bibit Jagung Hibrida',
                    'quantity' => 10,
                    'unit' => 'karung',
                    'unit_price' => 5000000,
                    'realized_amount' => 50000000,
                ],
            ],
            'documents' => [
                [
                    'document_type' => 'financial_report',
                    'document_title' => 'Laporan Realisasi Kas 100%',
                    'original_filename' => 'laporan_kas.pdf',
                    'storage_path' => 'lpj/laporan_kas.pdf',
                ],
            ],
        ]);
        $lpjId = $lpjRes->json('data.id');

        $submitRes = $this->postJson("/api/v1/lpj/{$lpjId}/submit");

        $submitRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertEquals(ProposalStatus::LPJ_SUBMITTED, $proposal->fresh()->status);
        $this->assertDatabaseHas('lpj_submissions', [
            'id' => $lpjId,
            'status' => 'submitted',
        ]);
    }

    public function test_authorized_reviewer_can_review_lpj(): void
    {
        $lpj = $this->createSubmittedLpj();

        Sanctum::actingAs($this->verifikator);

        $response = $this->postJson("/api/v1/lpj/{$lpj->id}/review", [
            'notes' => 'Pemeriksaan berkas SPJ dan bukti kuitansi fisik telah sesuai.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'under_review');

        $this->assertDatabaseHas('lpj_submissions', [
            'id' => $lpj->id,
            'status' => 'under_review',
            'verified_by' => $this->verifikator->id,
        ]);
    }

    public function test_maker_checker_rule_creator_cannot_approve_own_lpj(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        // Admin acts as creator (Maker)
        Sanctum::actingAs($this->admin);
        $lpjRes = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'LPJ dibuat oleh admin',
            'items' => [
                ['category' => 'A', 'item_name' => 'A', 'quantity' => 1, 'unit_price' => 1000000, 'realized_amount' => 1000000],
            ],
            'documents' => [
                ['document_type' => 'financial_report', 'document_title' => 'Doc', 'storage_path' => 'doc.pdf'],
            ],
        ]);
        $lpjId = $lpjRes->json('data.id');
        $this->postJson("/api/v1/lpj/{$lpjId}/submit")->assertOk();

        // Same admin tries to approve -> 422 Maker-Checker violation
        $attemptApprove = $this->postJson("/api/v1/lpj/{$lpjId}/approve");
        $attemptApprove->assertStatus(422)
            ->assertJsonValidationErrors(['actor']);

        // Different user (approver) approves -> 200 OK!
        Sanctum::actingAs($this->approver);
        $validApprove = $this->postJson("/api/v1/lpj/{$lpjId}/approve", [
            'notes' => 'Disetujui oleh pejabat berwenang.',
        ]);

        $validApprove->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_request_revision_requires_reason_and_transitions_proposal_to_revision(): void
    {
        $lpj = $this->createSubmittedLpj();

        Sanctum::actingAs($this->verifikator);

        // Empty reason -> 422
        $this->postJson("/api/v1/lpj/{$lpj->id}/request-revision", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        // Valid reason -> 200 OK
        $res = $this->postJson("/api/v1/lpj/{$lpj->id}/request-revision", [
            'reason' => 'Kuitansi nomor 04 belum dibubuhi meterai dan cap toko.',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'revision_requested');

        $this->assertEquals(ProposalStatus::REVISION, $lpj->proposal->fresh()->status);
    }

    public function test_reject_lpj_requires_reason_and_transitions_lpj_to_rejected(): void
    {
        $lpj = $this->createSubmittedLpj();

        Sanctum::actingAs($this->approver);

        $this->postJson("/api/v1/lpj/{$lpj->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $res = $this->postJson("/api/v1/lpj/{$lpj->id}/reject", [
            'reason' => 'Ditemukan ketidaksesuaian fiktif dalam bukti transaksi.',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_finalized_lpj_cannot_be_mutated(): void
    {
        $lpj = $this->createSubmittedLpj();

        Sanctum::actingAs($this->approver);
        $this->postJson("/api/v1/lpj/{$lpj->id}/approve")->assertOk();
        $this->postJson("/api/v1/lpj/{$lpj->id}/finalize")->assertOk();

        // Attempting to update finalized LPJ -> 403 or 422
        Sanctum::actingAs($this->pemohon);
        $this->patchJson("/api/v1/lpj/{$lpj->id}", ['summary' => 'Ubah draf'])
            ->assertForbidden();

        // Attempting to approve finalized LPJ -> 422
        Sanctum::actingAs($this->approver);
        $this->postJson("/api/v1/lpj/{$lpj->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_proposal_cannot_be_closed_if_disbursements_unpaid_or_lpj_unapproved(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        // 1. Proposal with no LPJ submitted -> closing rejected
        Sanctum::actingAs($this->approver);
        $summaryRes = $this->getJson("/api/v1/proposals/{$proposal->id}/closing-summary");
        $summaryRes->assertOk()
            ->assertJsonPath('data.is_eligible_for_close', false);

        $attemptClose = $this->postJson("/api/v1/proposals/{$proposal->id}/close");
        $attemptClose->assertStatus(422)
            ->assertJsonValidationErrors(['closing']);
    }

    public function test_proposal_can_be_closed_after_all_requirements_are_met(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        // Create and approve LPJ
        Sanctum::actingAs($this->pemohon);
        $lpjRes = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Realisasi 100%',
            'items' => [
                ['category' => 'Bibit', 'item_name' => 'Bibit Unggul', 'quantity' => 10, 'unit_price' => 5000000, 'realized_amount' => 50000000],
            ],
            'documents' => [
                ['document_type' => 'financial_report', 'document_title' => 'Laporan Akhir', 'storage_path' => 'lpj_akhir.pdf'],
            ],
        ]);
        $lpjId = $lpjRes->json('data.id');
        $this->postJson("/api/v1/lpj/{$lpjId}/submit")->assertOk();

        Sanctum::actingAs($this->approver);
        $this->postJson("/api/v1/lpj/{$lpjId}/approve")->assertOk();

        // Closing summary is now eligible
        $summary = $this->getJson("/api/v1/proposals/{$proposal->id}/closing-summary");
        $summary->assertOk()
            ->assertJsonPath('data.is_eligible_for_close', true)
            ->assertJsonPath('data.remaining_balance', 0);

        // Close proposal
        $closeRes = $this->postJson("/api/v1/proposals/{$proposal->id}/close", [
            'notes' => 'Kegiatan telah selesai 100% dan LPJ disetujui lengkap.',
        ]);

        $closeRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals(ProposalStatus::COMPLETED, $proposal->fresh()->status);
        $this->assertDatabaseHas('lpj_submissions', [
            'id' => $lpjId,
            'status' => 'closed',
        ]);
    }

    public function test_auditor_has_read_only_access_to_lpj_and_closing_summary(): void
    {
        $lpj = $this->createSubmittedLpj();

        Sanctum::actingAs($this->auditor);

        // Read access allowed
        $this->getJson('/api/v1/lpj')->assertOk();
        $this->getJson("/api/v1/lpj/{$lpj->id}")->assertOk();
        $this->getJson("/api/v1/proposals/{$lpj->proposal_id}/lpj")->assertOk();
        $this->getJson("/api/v1/proposals/{$lpj->proposal_id}/closing-summary")->assertOk();

        // Mutation operations forbidden
        $this->postJson("/api/v1/lpj/{$lpj->id}/approve")->assertForbidden();
        $this->postJson("/api/v1/lpj/{$lpj->id}/review")->assertForbidden();
        $this->postJson("/api/v1/lpj/{$lpj->id}/reject", ['reason' => 'Ditolak oleh auditor'])->assertForbidden();
        $this->postJson("/api/v1/proposals/{$lpj->proposal_id}/close")->assertForbidden();
    }

    public function test_audit_trail_and_notification_recorded_across_lpj_lifecycle(): void
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);
        $lpjRes = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'Audit test LPJ',
            'items' => [
                ['category' => 'A', 'item_name' => 'A', 'quantity' => 1, 'unit_price' => 50000000, 'realized_amount' => 50000000],
            ],
            'documents' => [
                ['document_type' => 'financial_report', 'document_title' => 'Doc', 'storage_path' => 'doc.pdf'],
            ],
        ]);
        $lpjId = $lpjRes->json('data.id');

        $this->postJson("/api/v1/lpj/{$lpjId}/submit")->assertOk();

        Sanctum::actingAs($this->verifikator);
        $this->postJson("/api/v1/lpj/{$lpjId}/review", ['notes' => 'Validasi awal'])->assertOk();

        Sanctum::actingAs($this->approver);
        $this->postJson("/api/v1/lpj/{$lpjId}/approve")->assertOk();
        $this->postJson("/api/v1/proposals/{$proposal->id}/close")->assertOk();

        // Check Audit Logs
        $this->assertTrue(AuditLog::where('action', 'lpj.created')->where('entity_id', $lpjId)->exists());
        $this->assertTrue(AuditLog::where('action', 'lpj.submitted')->where('entity_id', $lpjId)->exists());
        $this->assertTrue(AuditLog::where('action', 'lpj.reviewed')->where('entity_id', $lpjId)->exists());
        $this->assertTrue(AuditLog::where('action', 'lpj.approved')->where('entity_id', $lpjId)->exists());
        $this->assertTrue(AuditLog::where('action', 'proposal.closed')->where('entity_id', $proposal->id)->exists());

        // Check Notifications
        $this->assertTrue(Notification::where('type', 'lpj_submitted')->exists());
        $this->assertTrue(Notification::where('type', 'lpj_approved')->exists());
        $this->assertTrue(Notification::where('type', 'proposal_closed')->exists());
    }

    private function createDisbursedProposal(float $paidAmount = 50000000, ?User $applicant = null): Proposal
    {
        $applicant = $applicant ?: $this->pemohon;

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $applicant->id,
            'title' => 'Proposal Pengadaan Alat dan Bibit Tani',
            'status' => ProposalStatus::DISBURSED,
            'requested_amount' => $paidAmount,
            'approved_amount' => $paidAmount,
            'approved_at' => now(),
        ]);

        $decision = Decision::create([
            'proposal_id' => $proposal->id,
            'issued_by' => $this->approver->id,
            'decision_number' => 'SK-2026-'.Str::upper(Str::random(6)),
            'status' => 'published',
            'result' => 'approved',
            'decision_date' => now()->toDateString(),
            'approved_amount' => $paidAmount,
            'title' => 'Keputusan Hibah',
            'issued_at' => now(),
        ]);

        $plan = DisbursementPlan::create([
            'proposal_id' => $proposal->id,
            'created_by' => $this->approver->id,
            'plan_number' => 'PLAN-'.Str::upper(Str::random(8)),
            'total_stages' => 1,
            'planned_amount' => $paidAmount,
            'status' => DisbursementPlanStatus::COMPLETED,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        Disbursement::create([
            'disbursement_plan_id' => $plan->id,
            'proposal_id' => $proposal->id,
            'stage_number' => 1,
            'disbursement_number' => 'DISB-'.Str::upper(Str::random(8)),
            'planned_amount' => $paidAmount,
            'approved_amount' => $paidAmount,
            'paid_amount' => $paidAmount,
            'status' => DisbursementStatus::PAID,
            'paid_date' => now()->toDateString(),
        ]);

        return $proposal;
    }

    private function createSubmittedLpj(): LpjSubmission
    {
        $proposal = $this->createDisbursedProposal(paidAmount: 50000000);

        Sanctum::actingAs($this->pemohon);
        $res = $this->postJson("/api/v1/proposals/{$proposal->id}/lpj", [
            'summary' => 'LPJ Pertanian',
            'items' => [
                ['category' => 'Sarana', 'item_name' => 'Alat', 'quantity' => 1, 'unit_price' => 50000000, 'realized_amount' => 50000000],
            ],
            'documents' => [
                ['document_type' => 'financial_report', 'document_title' => 'Laporan', 'storage_path' => 'dokumen.pdf'],
            ],
        ]);

        $lpjId = $res->json('data.id');
        $this->postJson("/api/v1/lpj/{$lpjId}/submit");

        return LpjSubmission::find($lpjId);
    }
}
