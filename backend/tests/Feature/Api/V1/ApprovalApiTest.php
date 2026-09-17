<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApprovalStatus;
use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Enums\ProposalStatus;
use App\Enums\RankingStatus;
use App\Enums\RecommendationResult;
use App\Enums\RecommendationStatus;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\DecisionTemplate;
use App\Models\DecisionTemplateVersion;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\GrantProgram;
use App\Models\NumberingConfiguration;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Proposal;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\Recommendation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $approver;

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

        // Ensure permissions exist
        $permApprove = Permission::query()->firstOrCreate(
            ['code' => 'proposal.approve'],
            ['name' => 'Proposal Approve', 'module' => 'proposal', 'description' => 'Approve proposal']
        );
        $permReject = Permission::query()->firstOrCreate(
            ['code' => 'proposal.reject'],
            ['name' => 'Proposal Reject', 'module' => 'proposal', 'description' => 'Reject proposal']
        );
        $permViewAny = Permission::query()->firstOrCreate(
            ['code' => 'proposal.viewAny'],
            ['name' => 'Proposal View Any', 'module' => 'proposal', 'description' => 'View any proposal']
        );

        $approverRole->permissions()->syncWithoutDetaching([
            $permApprove->id,
            $permReject->id,
            $permViewAny->id,
        ]);

        $this->admin = User::factory()->create(['name' => 'Admin Sikomando']);
        $this->admin->roles()->sync([$superAdminRole->id]);

        $this->approver = User::factory()->create(['name' => 'Bupati / Approver']);
        $this->approver->roles()->sync([$approverRole->id]);

        $this->auditor = User::factory()->create(['name' => 'Auditor Inspektorat']);
        $this->auditor->roles()->sync([$auditorRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Kelompok Tani']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->surveyor = User::factory()->create(['name' => 'Petugas Lapangan']);
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->grantProgram = GrantProgram::factory()->create([
            'name' => 'Hibah Pemberdayaan Pertanian 2026',
            'is_active' => true,
        ]);
    }

    public function test_proposal_without_final_ranking_cannot_be_submitted_for_approval(): void
    {
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::RECOMMENDED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_proposal_without_official_recommendation_cannot_be_submitted_for_approval(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: false);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_unauthorized_actor_cannot_submit_or_approve_proposal(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        // Surveyor cannot submit approval
        Sanctum::actingAs($this->surveyor);
        $this->postJson("/api/v1/proposals/{$proposal->id}/approvals")
            ->assertForbidden();

        // Submit with admin
        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $submitRes->assertCreated();
        $approvalId = $submitRes->json('data.id');

        // Surveyor cannot approve
        Sanctum::actingAs($this->surveyor);
        $this->postJson("/api/v1/approvals/{$approvalId}/approve")
            ->assertForbidden();
    }

    public function test_maker_cannot_approve_own_submission_maker_checker_enforced(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        // User Admin submits approval
        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $submitRes->assertCreated();
        $approvalId = $submitRes->json('data.id');

        // Same admin tries to approve own submission -> 422 Maker-Checker violation
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve", [
            'notes' => 'Saya setujui sendiri pengajuan saya.',
        ]);

        $approveRes->assertStatus(422)
            ->assertJsonValidationErrors(['actor']);

        // Different user (approver) approves -> succeeds!
        Sanctum::actingAs($this->approver);
        $validApprove = $this->postJson("/api/v1/approvals/{$approvalId}/approve", [
            'notes' => 'Disetujui oleh pejabat berwenang.',
        ]);

        $validApprove->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_approval_successfully_changes_proposal_lifecycle_and_creates_decision(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        // 1. Submit for approval by admin
        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals", [
            'summary' => 'Pengajuan persetujuan bansos pertanian.',
        ]);
        $submitRes->assertCreated();
        $approvalId = $submitRes->json('data.id');

        // Proposal status is now 'approval'
        $this->assertEquals(ProposalStatus::APPROVAL, $proposal->fresh()->status);

        // 2. Approver reviews approval
        Sanctum::actingAs($this->approver);
        $reviewRes = $this->postJson("/api/v1/approvals/{$approvalId}/review", [
            'notes' => 'Pemeriksaan berkas dan ranking memenuhi syarat.',
        ]);
        $reviewRes->assertOk()
            ->assertJsonPath('data.status', 'under_review');

        // 3. Approver approves proposal
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve", [
            'approved_amount' => 45000000,
            'title' => 'Ketetapan Penerima Hibah Pertanian Poktan Subur',
            'notes' => 'Disetujui sesuai ketersediaan pagu anggaran.',
        ]);

        $approveRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.decision.result', 'approved')
            ->assertJsonPath('data.decision.approved_amount', 45000000);

        // Proposal status is now 'approved'
        $this->assertEquals(ProposalStatus::APPROVED, $proposal->fresh()->status);
        $this->assertNotNull($proposal->fresh()->approved_at);

        // Decision is saved
        $decision = Decision::where('approval_id', $approvalId)->first();
        $this->assertNotNull($decision);
        $this->assertEquals(DecisionResult::APPROVED, $decision->result);
        $this->assertEquals(45000000, $decision->approved_amount);
    }

    public function test_reject_approval_requires_mandatory_reason_and_updates_lifecycle(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);

        // Reject without reason fails validation
        $failReject = $this->postJson("/api/v1/approvals/{$approvalId}/reject", [
            'reason' => '',
        ]);
        $failReject->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        // Reject with reason succeeds
        $rejectRes = $this->postJson("/api/v1/approvals/{$approvalId}/reject", [
            'reason' => 'Pagu anggaran daerah untuk program ini telah habis diserap.',
        ]);

        $rejectRes->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.decision.result', 'rejected')
            ->assertJsonPath('data.decision.reason', 'Pagu anggaran daerah untuk program ini telah habis diserap.');

        // Proposal status is now 'rejected'
        $this->assertEquals(ProposalStatus::REJECTED, $proposal->fresh()->status);
    }

    public function test_finalized_approval_and_decision_cannot_be_re_approved_or_mutated(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $this->postJson("/api/v1/approvals/{$approvalId}/approve")->assertOk();

        // Attempt to re-approve
        $reApprove = $this->postJson("/api/v1/approvals/{$approvalId}/approve");
        $reApprove->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Attempt to reject already approved
        $reReject = $this->postJson("/api/v1/approvals/{$approvalId}/reject", [
            'reason' => 'Mau membatalkan.',
        ]);
        $reReject->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_sk_document_generation_uses_dynamic_numbering_and_template_from_policy(): void
    {
        // 1. Setup custom numbering configuration
        NumberingConfiguration::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'document_type' => 'decision',
            'prefix' => 'SK-BUPATI',
            'format_pattern' => '{prefix}/{year}/{month}/{seq}',
            'status' => 'active',
        ]);

        // 2. Setup custom decision template & version
        $template = DecisionTemplate::factory()->create([
            'code' => 'SK_PENETAPAN_HIBAH',
            'name' => 'SK Penetapan Bupati',
            'document_type' => 'decision_letter',
            'is_active' => true,
        ]);

        DecisionTemplateVersion::factory()->create([
            'decision_template_id' => $template->id,
            'version_number' => '2026.1',
            'template_content' => '<p>Ketetapan Nomor {{decision_number}} untuk {{proposal_title}} dengan dana {{approved_amount}}.</p>',
            'status' => 'active',
        ]);

        // 3. Complete proposal approval
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true, title: 'Poktan Makmur Sentosa');

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve", [
            'approved_amount' => 50000000,
        ]);
        $decisionId = $approveRes->json('data.decision.id');

        // Verify decision number follows configured pattern
        $this->assertStringStartsWith('SK-BUPATI/' . date('Y') . '/' . date('m') . '/0001', $approveRes->json('data.decision.decision_number'));

        // 4. Generate SK Document
        $docRes = $this->postJson("/api/v1/decisions/{$decisionId}/documents", [
            'notes' => 'Penerbitan SK resmi tahap 1.',
        ]);

        $docRes->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document_type', 'decision_letter')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'active');

        $docId = $docRes->json('data.id');

        // Verify document exists in private storage and contains rendered variables
        $document = DecisionDocument::findOrFail($docId);
        $this->assertTrue(Storage::disk('private')->exists($document->storage_path));
        $content = Storage::disk('private')->get($document->storage_path);
        $this->assertStringContainsString('Poktan Makmur Sentosa', $content);
        $this->assertStringContainsString('Rp 50.000.000', $content);
    }

    public function test_sk_document_generation_requires_approved_decision(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $rejectRes = $this->postJson("/api/v1/approvals/{$approvalId}/reject", [
            'reason' => 'Ditolak karena tidak ada anggaran.',
        ]);
        $decisionId = $rejectRes->json('data.decision.id');

        // Cannot generate document for rejected decision
        $docRes = $this->postJson("/api/v1/decisions/{$decisionId}/documents");
        $docRes->assertStatus(422)
            ->assertJsonValidationErrors(['decision']);
    }

    public function test_sk_number_is_unique_and_increments_consecutively(): void
    {
        NumberingConfiguration::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'document_type' => 'decision',
            'prefix' => 'SK-HIBAH',
            'format_pattern' => '{prefix}/{year}/{seq}',
            'status' => 'active',
        ]);

        $proposal1 = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true, title: 'Proposal 1');
        $proposal2 = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true, title: 'Proposal 2');

        Sanctum::actingAs($this->admin);
        $sub1 = $this->postJson("/api/v1/proposals/{$proposal1->id}/approvals");
        $sub2 = $this->postJson("/api/v1/proposals/{$proposal2->id}/approvals");

        Sanctum::actingAs($this->approver);
        $app1 = $this->postJson("/api/v1/approvals/{$sub1->json('data.id')}/approve");
        $app2 = $this->postJson("/api/v1/approvals/{$sub2->json('data.id')}/approve");

        $num1 = $app1->json('data.decision.decision_number');
        $num2 = $app2->json('data.decision.decision_number');

        $this->assertEquals('SK-HIBAH/' . date('Y') . '/0001', $num1);
        $this->assertEquals('SK-HIBAH/' . date('Y') . '/0002', $num2);
        $this->assertNotEquals($num1, $num2);
    }

    public function test_auditor_can_read_approvals_and_decisions_but_cannot_mutate(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve");
        $decisionId = $approveRes->json('data.decision.id');

        // Auditor read-only access
        Sanctum::actingAs($this->auditor);

        $this->getJson('/api/v1/approvals')->assertOk()->assertJsonPath('success', true);
        $this->getJson("/api/v1/approvals/{$approvalId}")->assertOk();
        $this->getJson('/api/v1/decisions')->assertOk()->assertJsonPath('success', true);
        $this->getJson("/api/v1/decisions/{$decisionId}")->assertOk();

        // Auditor cannot generate document or mutate
        $this->postJson("/api/v1/decisions/{$decisionId}/documents")->assertForbidden();
        $this->postJson("/api/v1/approvals/{$approvalId}/approve")->assertForbidden();
    }

    public function test_pemohon_cannot_access_internal_approvals_but_can_view_own_decision_and_download_sk(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);
        $otherProposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true, applicant: User::factory()->create());

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve");
        $decisionId = $approveRes->json('data.decision.id');

        $docRes = $this->postJson("/api/v1/decisions/{$decisionId}/documents");
        $docId = $docRes->json('data.id');

        // Pemohon tests
        Sanctum::actingAs($this->pemohon);

        // Internal approval forbidden
        $this->getJson('/api/v1/approvals')->assertForbidden();
        $this->getJson("/api/v1/approvals/{$approvalId}")->assertForbidden();

        // Own proposal decision is accessible
        $myDecisionRes = $this->getJson("/api/v1/proposals/{$proposal->id}/decision");
        $myDecisionRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $decisionId);

        // Other applicant proposal decision is forbidden
        $this->getJson("/api/v1/proposals/{$otherProposal->id}/decision")
            ->assertForbidden();

        // Can download own SK document
        $downloadRes = $this->getJson("/api/v1/decisions/{$decisionId}/documents/{$docId}/download");
        $downloadRes->assertOk();
    }

    public function test_cross_decision_document_mismatch_returns_404(): void
    {
        $proposalA = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);
        $proposalB = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitA = $this->postJson("/api/v1/proposals/{$proposalA->id}/approvals");
        $submitB = $this->postJson("/api/v1/proposals/{$proposalB->id}/approvals");

        Sanctum::actingAs($this->approver);
        $appA = $this->postJson("/api/v1/approvals/{$submitA->json('data.id')}/approve");
        $appB = $this->postJson("/api/v1/approvals/{$submitB->json('data.id')}/approve");

        $decA = $appA->json('data.decision.id');
        $decB = $appB->json('data.decision.id');

        $docB = $this->postJson("/api/v1/decisions/{$decB}/documents")->json('data.id');

        // Accessing docB under decA URL returns 404
        $this->getJson("/api/v1/decisions/{$decA}/documents/{$docB}")
            ->assertNotFound();
    }

    public function test_audit_trail_recorded_across_approval_and_decision_lifecycle(): void
    {
        $proposal = $this->createCandidateProposal(withFinalRanking: true, isRecommended: true);

        Sanctum::actingAs($this->admin);
        $submitRes = $this->postJson("/api/v1/proposals/{$proposal->id}/approvals");
        $approvalId = $submitRes->json('data.id');

        Sanctum::actingAs($this->approver);
        $approveRes = $this->postJson("/api/v1/approvals/{$approvalId}/approve");
        $decisionId = $approveRes->json('data.decision.id');

        $this->postJson("/api/v1/decisions/{$decisionId}/documents");

        // Assert audit logs exist
        $this->assertTrue(AuditLog::where('action', 'approval.submitted')->where('entity_id', $approvalId)->exists());
        $this->assertTrue(AuditLog::where('action', 'approval.approved')->where('entity_id', $approvalId)->exists());
        $this->assertTrue(AuditLog::where('action', 'decision.created')->where('entity_id', $decisionId)->exists());
        $this->assertTrue(AuditLog::where('action', 'decision.document_generated')->exists());
    }

    private function createCandidateProposal(
        bool $withFinalRanking = true,
        bool $isRecommended = true,
        string $title = 'Proposal Uji',
        ?User $applicant = null
    ): Proposal {
        $applicant = $applicant ?: $this->pemohon;

        $org = Organization::factory()->create([
            'name' => 'Kelompok Tani Subur Makmur',
        ]);

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'applicant_id' => $applicant->id,
            'organization_id' => $org->id,
            'title' => $title,
            'status' => ProposalStatus::RECOMMENDED,
            'requested_amount' => 50000000,
        ]);

        Evaluation::factory()->create([
            'proposal_id' => $proposal->id,
            'evaluator_id' => User::factory()->create()->id,
            'status' => EvaluationStatus::COMPLETED,
            'result' => EvaluationResult::RECOMMENDED,
            'final_score' => 85.0,
            'total_score' => 85.0,
            'completed_at' => now(),
        ]);

        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::COMPLETED,
            'result' => FieldSurveyResult::RECOMMENDED,
            'completed_at' => now(),
        ]);

        foreach ($survey->items as $item) {
            $item->update(['result' => FieldSurveyItemResult::PASS]);
        }

        if ($withFinalRanking) {
            $ranking = Ranking::factory()->create([
                'grant_program_id' => $this->grantProgram->id,
                'status' => RankingStatus::FINALIZED,
                'finalized_by' => $this->admin->id,
                'finalized_at' => now(),
            ]);

            RankingItem::create([
                'ranking_id' => $ranking->id,
                'proposal_id' => $proposal->id,
                'evaluation_score' => 85.0,
                'survey_score' => 100.0,
                'final_score' => 91.0,
                'rank' => 1,
                'recommendation_result' => $isRecommended ? 'recommended' : 'not_recommended',
                'recommendation_reason' => $isRecommended ? 'Memenuhi kriteria' : 'Tidak memenuhi kriteria',
                'snapshot_data' => ['evaluation' => ['result' => 'completed']],
            ]);

            Recommendation::create([
                'proposal_id' => $proposal->id,
                'recommended_by' => $this->admin->id,
                'recommendation_number' => 'REC-' . Str::upper(Str::random(8)),
                'status' => RecommendationStatus::COMPLETED,
                'result' => $isRecommended ? RecommendationResult::RECOMMENDED : RecommendationResult::NOT_RECOMMENDED,
                'recommended_amount' => $isRecommended ? $proposal->requested_amount : 0,
                'summary' => 'Rekomendasi resmi final.',
                'reason' => 'Lolos perangkingan.',
                'completed_at' => now(),
            ]);
        }

        return $proposal;
    }
}
