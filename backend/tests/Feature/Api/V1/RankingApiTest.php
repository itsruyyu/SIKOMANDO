<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Enums\ProposalStatus;
use App\Enums\RankingStatus;
use App\Enums\RecommendationResult;
use App\Models\Evaluation;
use App\Models\FieldSurvey;
use App\Models\GrantProgram;
use App\Models\Proposal;
use App\Models\Ranking;
use App\Models\RankingRuleConfiguration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RankingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $auditor;

    private User $pemohon;

    private User $evaluator;

    private User $surveyor;

    private GrantProgram $grantProgram;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Admin', 'is_system' => true, 'is_active' => true]
        );

        $auditorRole = Role::query()->firstOrCreate(
            ['code' => 'AUDITOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Auditor', 'is_system' => false, 'is_active' => true]
        );

        $pemohonRole = Role::query()->firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => false, 'is_active' => true]
        );

        $evaluatorRole = Role::query()->firstOrCreate(
            ['code' => 'EVALUATOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Evaluator', 'is_system' => false, 'is_active' => true]
        );

        $surveyorRole = Role::query()->firstOrCreate(
            ['code' => 'SURVEYOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Surveyor', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$adminRole->id]);

        $this->auditor = User::factory()->create();
        $this->auditor->roles()->sync([$auditorRole->id]);

        $this->pemohon = User::factory()->create();
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->evaluator = User::factory()->create();
        $this->evaluator->roles()->sync([$evaluatorRole->id]);

        $this->surveyor = User::factory()->create();
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->grantProgram = GrantProgram::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_admin_and_auditor_can_preview_ranking(): void
    {
        // Setup 2 eligible proposals
        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 80.0,
            surveyPass: true,
            title: 'Proposal A'
        );

        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 90.0,
            surveyPass: true,
            title: 'Proposal B'
        );

        // 1. Admin preview
        Sanctum::actingAs($this->admin);
        $adminResponse = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview"
        );

        $adminResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statistics.total_candidates', 2)
            ->assertJsonPath('data.items.0.proposal_title', 'Proposal B')
            ->assertJsonPath('data.items.0.rank', 1)
            ->assertJsonPath('data.items.1.proposal_title', 'Proposal A')
            ->assertJsonPath('data.items.1.rank', 2);

        // Preview does NOT save ranking to DB
        $this->assertDatabaseCount('rankings', 0);

        // 2. Auditor can also preview
        Sanctum::actingAs($this->auditor);
        $auditorResponse = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview"
        );
        $auditorResponse->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_proposal_with_incomplete_evaluation_is_excluded_from_ranking(): void
    {
        // Proposal with IN_PROGRESS evaluation
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'status' => ProposalStatus::EVALUATION,
        ]);

        Evaluation::factory()->create([
            'proposal_id' => $proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::IN_PROGRESS,
            'final_score' => 85.0,
        ]);

        FieldSurvey::factory()->completed()->create([
            'proposal_id' => $proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview"
        );

        $response->assertOk()
            ->assertJsonPath('data.statistics.total_candidates', 0);
    }

    public function test_proposal_with_non_final_field_survey_is_excluded_from_ranking(): void
    {
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $this->grantProgram->id,
            'status' => ProposalStatus::SURVEY,
        ]);

        Evaluation::factory()->create([
            'proposal_id' => $proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::COMPLETED,
            'final_score' => 85.0,
        ]);

        // Survey still IN_PROGRESS (non-final)
        FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview"
        );

        $response->assertOk()
            ->assertJsonPath('data.statistics.total_candidates', 0);
    }

    public function test_weights_are_dynamically_resolved_from_active_ranking_rule(): void
    {
        // Custom Rule: 80% Evaluasi, 20% Survei, Passing Grade 75.0
        RankingRuleConfiguration::query()->create([
            'grant_program_id' => $this->grantProgram->id,
            'rule_code' => 'CUSTOM_80_20',
            'rule_name' => 'Aturan Bobot 80/20',
            'rule_definition' => [
                'evaluation_weight' => 80.0,
                'survey_weight' => 20.0,
                'passing_grade' => 75.0,
                'minimum_evaluation_score' => 60.0,
                'minimum_survey_score' => 50.0,
            ],
            'priority' => 10,
            'status' => 'approved',
        ]);

        // Proposal dengan Evaluasi 80.0, Survei 100.0
        // Formula: (80 * 0.8) + (100 * 0.2) = 64 + 20 = 84.0
        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 80.0,
            surveyPass: true,
            title: 'Proposal Bobot Dinamis'
        );

        Sanctum::actingAs($this->admin);

        $response = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview"
        );

        $response->assertOk()
            ->assertJsonPath('data.parameters.rule_code', 'CUSTOM_80_20')
            ->assertJsonPath('data.parameters.evaluation_weight', 80)
            ->assertJsonPath('data.parameters.survey_weight', 20)
            ->assertJsonPath('data.items.0.final_score', 84);
    }

    public function test_admin_can_generate_ranking_and_records_audit_trail(): void
    {
        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 85.0,
            surveyPass: true,
            title: 'Proposal 1'
        );

        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 75.0,
            surveyPass: true,
            title: 'Proposal 2'
        );

        Sanctum::actingAs($this->admin);

        $response = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate",
            [
                'notes' => 'Eksekusi perangkingan perdana.',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RankingStatus::GENERATED->value)
            ->assertJsonPath('data.total_proposals', 2)
            ->assertJsonPath('data.recommended_count', 2);

        $rankingId = $response->json('data.id');

        $this->assertDatabaseHas('rankings', [
            'id' => $rankingId,
            'grant_program_id' => $this->grantProgram->id,
            'status' => RankingStatus::GENERATED->value,
            'total_proposals' => 2,
        ]);

        $this->assertDatabaseCount('ranking_items', 2);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ranking.generated',
            'entity_id' => $rankingId,
        ]);
    }

    public function test_ranking_snapshot_is_preserved_even_if_policy_rule_changes(): void
    {
        // 1. Initial rule: 60/40
        $this->createCandidateProposal(
            program: $this->grantProgram,
            evalScore: 80.0,
            surveyPass: true,
        );

        Sanctum::actingAs($this->admin);

        $genResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        );
        $genResponse->assertCreated();
        $rankingId = $genResponse->json('data.id');

        // Score with 60/40 is (80*0.6) + (100*0.4) = 48 + 40 = 88.0
        $this->assertEquals(88.0, $genResponse->json('data.items.0.final_score'));

        // 2. Now change rule configuration in DB to 90/10
        RankingRuleConfiguration::query()->create([
            'grant_program_id' => $this->grantProgram->id,
            'rule_code' => 'NEW_90_10',
            'rule_name' => 'Aturan Baru 90/10',
            'rule_definition' => [
                'evaluation_weight' => 90.0,
                'survey_weight' => 10.0,
                'passing_grade' => 70.0,
            ],
            'priority' => 99,
            'status' => 'approved',
        ]);

        // 3. Re-read the generated ranking via show API
        $showResponse = $this->getJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}"
        );

        // Final score and weights snapshot MUST REMAIN UNCHANGED (88.0)
        $showResponse->assertOk()
            ->assertJsonPath('data.items.0.final_score', 88)
            ->assertJsonPath('data.weights_snapshot.evaluation_weight', 60);
    }

    public function test_admin_can_review_ranking(): void
    {
        $this->createCandidateProposal($this->grantProgram, 85.0, true);

        Sanctum::actingAs($this->admin);

        $genResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        );
        $rankingId = $genResponse->json('data.id');

        $reviewResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/review",
            [
                'action' => 'reviewed',
                'notes' => 'Hasil perangkingan telah diverifikasi oleh tim penilai.',
            ]
        );

        $reviewResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RankingStatus::REVIEWED->value)
            ->assertJsonPath('data.notes', 'Hasil perangkingan telah diverifikasi oleh tim penilai.');

        $this->assertDatabaseHas('rankings', [
            'id' => $rankingId,
            'status' => RankingStatus::REVIEWED->value,
            'reviewed_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ranking.reviewed',
            'entity_id' => $rankingId,
        ]);
    }

    public function test_admin_can_regenerate_non_final_ranking(): void
    {
        $this->createCandidateProposal($this->grantProgram, 75.0, true);

        Sanctum::actingAs($this->admin);

        $genResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        );
        $rankingId = $genResponse->json('data.id');
        $this->assertEquals(1, $genResponse->json('data.total_proposals'));

        // Add another candidate proposal
        $this->createCandidateProposal($this->grantProgram, 95.0, true);

        // Regenerate
        $regenResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/regenerate",
            ['notes' => 'Regenerate setelah penambahan hasil survei.']
        );

        $regenResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_proposals', 2)
            ->assertJsonPath('data.items.0.rank', 1)
            ->assertJsonPath('data.items.0.evaluation_score', 95);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ranking.regenerated',
            'entity_id' => $rankingId,
        ]);
    }

    public function test_admin_can_finalize_ranking_and_syncs_official_recommendations(): void
    {
        $proposalA = $this->createCandidateProposal($this->grantProgram, 85.0, true, 'Proposal Lolos');
        $proposalB = $this->createCandidateProposal($this->grantProgram, 40.0, true, 'Proposal Tidak Lolos');

        Sanctum::actingAs($this->admin);

        $genResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        );
        $rankingId = $genResponse->json('data.id');

        // Finalize ranking
        $finalizeResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/finalize",
            ['notes' => 'Pengesahan final ranking penerima hibah.']
        );

        $finalizeResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RankingStatus::FINALIZED->value);

        $this->assertDatabaseHas('rankings', [
            'id' => $rankingId,
            'status' => RankingStatus::FINALIZED->value,
            'finalized_by' => $this->admin->id,
        ]);

        // Official recommendations must be generated in recommendations table
        $this->assertDatabaseHas('recommendations', [
            'proposal_id' => $proposalA->id,
            'result' => RecommendationResult::RECOMMENDED->value,
        ]);

        $this->assertDatabaseHas('recommendations', [
            'proposal_id' => $proposalB->id,
            'result' => RecommendationResult::NOT_RECOMMENDED->value,
        ]);

        // Recommendation breakdown items created
        $this->assertDatabaseHas('recommendation_items', [
            'item_code' => 'EVAL',
        ]);
        $this->assertDatabaseHas('recommendation_items', [
            'item_code' => 'SURVEY',
        ]);
        $this->assertDatabaseHas('recommendation_items', [
            'item_code' => 'FINAL_RANK',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ranking.finalized',
            'entity_id' => $rankingId,
        ]);

        // User / applicant can view recommendation
        Sanctum::actingAs($this->admin);
        $recResponse = $this->getJson(
            "/api/v1/proposals/{$proposalA->id}/recommendation"
        );

        $recResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result', RecommendationResult::RECOMMENDED->value)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_cannot_regenerate_or_finalize_already_finalized_ranking(): void
    {
        $this->createCandidateProposal($this->grantProgram, 85.0, true);

        Sanctum::actingAs($this->admin);

        $genResponse = $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        );
        $rankingId = $genResponse->json('data.id');

        // Finalize
        $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/finalize"
        )->assertOk();

        // Cannot regenerate finalized ranking (422)
        $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/regenerate"
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['ranking']);

        // Cannot finalize again (422)
        $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}/finalize"
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['ranking']);
    }

    public function test_non_admin_cannot_generate_or_finalize_ranking(): void
    {
        $this->createCandidateProposal($this->grantProgram, 85.0, true);

        // Auditor cannot generate
        Sanctum::actingAs($this->auditor);
        $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        )->assertForbidden();

        // Pemohon cannot generate
        Sanctum::actingAs($this->pemohon);
        $this->postJson(
            "/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate"
        )->assertForbidden();
    }

    public function test_pemohon_cannot_access_internal_ranking_endpoints(): void
    {
        $this->createCandidateProposal($this->grantProgram, 85.0, true);

        Sanctum::actingAs($this->admin);
        $gen = $this->postJson("/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate");
        $rankingId = $gen->json('data.id');

        Sanctum::actingAs($this->pemohon);

        // List rankings forbidden
        $this->getJson("/api/v1/grant-programs/{$this->grantProgram->id}/rankings")
            ->assertForbidden();

        // Show ranking forbidden
        $this->getJson("/api/v1/grant-programs/{$this->grantProgram->id}/rankings/{$rankingId}")
            ->assertForbidden();

        // Preview forbidden
        $this->getJson("/api/v1/grant-programs/{$this->grantProgram->id}/rankings/preview")
            ->assertForbidden();
    }

    public function test_cross_program_ranking_access_returns_404(): void
    {
        $otherProgram = GrantProgram::factory()->create();

        $this->createCandidateProposal($this->grantProgram, 85.0, true);

        Sanctum::actingAs($this->admin);
        $gen = $this->postJson("/api/v1/grant-programs/{$this->grantProgram->id}/rankings/generate");
        $rankingId = $gen->json('data.id');

        // Request ranking belonging to grantProgram using otherProgram in URL -> 404
        $response = $this->getJson("/api/v1/grant-programs/{$otherProgram->id}/rankings/{$rankingId}");
        $response->assertNotFound();
    }

    private function createCandidateProposal(
        GrantProgram $program,
        float $evalScore,
        bool $surveyPass = true,
        string $title = 'Proposal Uji'
    ): Proposal {
        $applicant = User::factory()->create();

        $proposal = Proposal::factory()->create([
            'grant_program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'title' => $title,
            'status' => ProposalStatus::RECOMMENDED,
            'requested_amount' => 50000000,
        ]);

        Evaluation::factory()->create([
            'proposal_id' => $proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::COMPLETED,
            'result' => $evalScore >= 60.0 ? EvaluationResult::RECOMMENDED : EvaluationResult::NOT_RECOMMENDED,
            'final_score' => $evalScore,
            'total_score' => $evalScore,
            'completed_at' => now(),
        ]);

        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => $surveyPass ? FieldSurveyStatus::COMPLETED : FieldSurveyStatus::REJECTED,
            'result' => $surveyPass ? FieldSurveyResult::RECOMMENDED : FieldSurveyResult::NOT_RECOMMENDED,
            'completed_at' => now(),
        ]);

        // Update all items for survey
        foreach ($survey->items as $item) {
            $item->update([
                'result' => $surveyPass ? FieldSurveyItemResult::PASS : FieldSurveyItemResult::FAIL,
            ]);
        }

        return $proposal;
    }
}
