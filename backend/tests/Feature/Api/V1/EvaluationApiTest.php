<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EvaluationStatus;
use App\Enums\ProposalStatus;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationWeightConfiguration;
use App\Models\PolicyConfiguration;
use App\Models\PolicyVersion;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $evaluator;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();

        $evaluatorRole = Role::query()->firstOrCreate(
            [
                'code' => 'EVALUATOR',
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'EVALUATOR',
                'description' => 'Petugas evaluator proposal.',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        Role::query()->firstOrCreate(
            [
                'code' => 'AUDITOR',
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'AUDITOR',
                'description' => 'Petugas auditor.',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->evaluator = User::factory()->create();

        $this->evaluator->roles()->syncWithoutDetaching([
            $evaluatorRole->id,
        ]);

        $this->proposal = Proposal::factory()->create([
            'applicant_id' => $this->evaluator->id,
            'status' => ProposalStatus::VERIFIED,
        ]);

        EvaluationCriteria::query()->create([
            'code' => 'CRITERIA-'.Str::upper(Str::random(8)),
            'name' => 'Kelayakan Program',
            'description' => 'Penilaian kelayakan program.',
            'criterion_type' => 'score',
            'default_weight' => 50,
            'minimum_score' => 0,
            'maximum_score' => 100,
            'is_active' => true,
        ]);

        EvaluationCriteria::query()->create([
            'code' => 'CRITERIA-'.Str::upper(Str::random(8)),
            'name' => 'Kapasitas Organisasi',
            'description' => 'Penilaian kapasitas organisasi.',
            'criterion_type' => 'score',
            'default_weight' => 50,
            'minimum_score' => 0,
            'maximum_score' => 100,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->evaluator);
    }

    public function test_evaluator_can_create_evaluation(): void
    {
        $this->createEvaluationConfiguration();

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations",
            [
                'notes' => 'Evaluasi awal proposal.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('data.proposal_id', $this->proposal->id)
            ->assertJsonPath('data.evaluator_id', $this->evaluator->id)
            ->assertJsonPath('data.status', 'IN_PROGRESS')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('evaluations', [
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);
    }

    public function test_evaluator_can_list_evaluations_for_proposal(): void
    {
        Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_evaluator_can_view_evaluation_detail(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $evaluation->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'proposal_id',
                    'evaluator_id',
                    'evaluation_number',
                    'status',
                    'total_score',
                    'final_score',
                    'result',
                    'items',
                ],
            ]);
    }

    public function test_evaluator_can_update_evaluation_item(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $item = $evaluation->items()->first();

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}",
            [
                'score' => 85,
                'notes' => 'Dokumen dan indikator memenuhi kriteria.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $evaluation->id);

        $this->assertDatabaseHas('evaluation_items', [
            'id' => $item->id,
            'score' => 85,
            'notes' => 'Dokumen dan indikator memenuhi kriteria.',
        ]);
    }

    public function test_evaluation_cannot_be_completed_before_all_items_are_scored(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/complete",
            [
                'summary' => 'Evaluasi selesai.',
            ]
        );

        $response->assertStatus(422);
    }

    public function test_evaluator_can_complete_evaluation_after_scoring_all_items(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        foreach ($evaluation->items as $item) {
            $item->update([
                'score' => 85,
                'weighted_score' => 42.5,
                'result' => 'PASS',
                'scored_at' => now(),
            ]);
        }

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/complete",
            [
                'summary' => 'Evaluasi selesai dan seluruh kriteria telah dinilai.',
                'notes' => 'Tidak terdapat catatan tambahan.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.result', 'RECOMMENDED');

        $this->assertDatabaseHas('evaluations', [
            'id' => $evaluation->id,
            'status' => 'COMPLETED',
            'result' => 'RECOMMENDED',
        ]);
    }

    public function test_evaluator_cannot_view_another_evaluators_evaluation(): void
    {
        $anotherEvaluator = User::factory()->create();

        $evaluatorRole = Role::query()
            ->where('code', 'EVALUATOR')
            ->firstOrFail();

        $anotherEvaluator->roles()->syncWithoutDetaching([
            $evaluatorRole->id,
        ]);

        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $anotherEvaluator->id,
        ]);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}"
        );

        $response->assertForbidden();
    }

    public function test_non_evaluator_cannot_create_evaluation(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations",
            [
                'notes' => 'Percobaan tidak berwenang.',
            ]
        );

        $response->assertForbidden();
    }

    public function test_evaluator_cannot_create_evaluation_if_proposal_not_in_valid_status(): void
    {
        $draftProposal = Proposal::factory()->create([
            'applicant_id' => $this->evaluator->id,
            'status' => ProposalStatus::DRAFT,
        ]);

        $response = $this->postJson(
            "/api/v1/proposals/{$draftProposal->id}/evaluations",
            [
                'notes' => 'Percobaan evaluasi proposal draft.',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_evaluator_cannot_create_duplicate_active_evaluation(): void
    {
        Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::IN_PROGRESS,
        ]);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations",
            [
                'notes' => 'Percobaan evaluasi ganda.',
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['evaluation']);
    }

    public function test_evaluator_cannot_update_item_with_score_out_of_bounds(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $item = $evaluation->items()->first();

        // Testing score exceeding maximum score (100)
        $responseAbove = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}",
            [
                'score' => 150,
            ]
        );
        $responseAbove->assertStatus(422)
            ->assertJsonValidationErrors(['score']);

        // Testing score below minimum score (0)
        $responseBelow = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}",
            [
                'score' => -10,
            ]
        );
        $responseBelow->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_evaluator_cannot_update_item_belonging_to_another_evaluation(): void
    {
        $evaluationA = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $evaluationB = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $itemFromB = $evaluationB->items()->first();

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluationA->id}/items/{$itemFromB->id}",
            [
                'score' => 80,
            ]
        );

        $response->assertStatus(404);
    }

    public function test_evaluator_cannot_view_evaluation_belonging_to_another_proposal(): void
    {
        $anotherProposal = Proposal::factory()->create([
            'status' => ProposalStatus::VERIFIED,
        ]);

        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $anotherProposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}"
        );

        $response->assertStatus(404);
    }

    public function test_evaluator_cannot_modify_or_recomplete_completed_evaluation(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::COMPLETED,
        ]);

        $item = $evaluation->items()->first();

        // Attempting to update item on completed evaluation
        $patchResponse = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}",
            [
                'score' => 90,
            ]
        );
        $patchResponse->assertForbidden();

        // Attempting to complete already completed evaluation
        $completeResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/complete",
            [
                'summary' => 'Selesaikan ulang.',
            ]
        );
        $completeResponse->assertForbidden();
    }

    public function test_auditor_can_view_evaluations_but_cannot_mutate(): void
    {
        $auditor = User::factory()->create();
        $auditorRole = Role::query()->where('code', 'AUDITOR')->firstOrFail();
        $auditor->roles()->syncWithoutDetaching([$auditorRole->id]);

        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $item = $evaluation->items()->first();

        Sanctum::actingAs($auditor);

        // Auditor can view list
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/evaluations")
            ->assertOk();

        // Auditor can view detail
        $this->getJson("/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}")
            ->assertOk();

        // Auditor cannot create
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/evaluations", ['notes' => 'test'])
            ->assertForbidden();

        // Auditor cannot update item
        $this->patchJson("/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}", ['score' => 80])
            ->assertForbidden();

        // Auditor cannot complete
        $this->postJson("/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/complete", ['summary' => 'test'])
            ->assertForbidden();

        // Auditor cannot delete
        $this->deleteJson("/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}")
            ->assertForbidden();
    }

    public function test_evaluator_cannot_update_another_evaluators_evaluation_item(): void
    {
        $anotherEvaluator = User::factory()->create();
        $evaluatorRole = Role::query()->where('code', 'EVALUATOR')->firstOrFail();
        $anotherEvaluator->roles()->syncWithoutDetaching([$evaluatorRole->id]);

        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $anotherEvaluator->id,
        ]);

        $item = $evaluation->items()->first();

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}/items/{$item->id}",
            [
                'score' => 80,
            ]
        );

        $response->assertForbidden();
    }

    public function test_evaluator_can_delete_in_progress_evaluation(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::IN_PROGRESS,
        ]);

        $response = $this->deleteJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('evaluations', [
            'id' => $evaluation->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'evaluation.deleted',
            'entity_id' => $evaluation->id,
        ]);
    }

    public function test_evaluator_cannot_delete_completed_evaluation(): void
    {
        $evaluation = Evaluation::factory()->create([
            'proposal_id' => $this->proposal->id,
            'evaluator_id' => $this->evaluator->id,
            'status' => EvaluationStatus::COMPLETED,
        ]);

        $response = $this->deleteJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluation->id}"
        );

        $response->assertForbidden();
    }

    public function test_evaluation_lifecycle_records_audit_trail(): void
    {
        $this->createEvaluationConfiguration();

        // 1. Create
        $createResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations",
            ['notes' => 'Awal evaluasi audit trail.']
        );
        $createResponse->assertCreated();
        $evaluationId = $createResponse->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'evaluation.created',
            'entity_id' => $evaluationId,
        ]);

        $evaluation = Evaluation::with('items')->findOrFail($evaluationId);

        // 2. Update all items
        foreach ($evaluation->items as $item) {
            $this->patchJson(
                "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluationId}/items/{$item->id}",
                ['score' => 90]
            )->assertOk();

            $this->assertDatabaseHas('audit_logs', [
                'action' => 'evaluation.item_updated',
                'entity_id' => $item->id,
            ]);
        }

        // 3. Complete
        $completeResponse = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/evaluations/{$evaluationId}/complete",
            ['summary' => 'Audit log evaluasi lengkap.']
        );
        $completeResponse->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'evaluation.completed',
            'entity_id' => $evaluationId,
        ]);

        // Verify proposal transitioned
        $this->proposal->refresh();
        $this->assertEquals(ProposalStatus::RECOMMENDED, $this->proposal->status);
    }

    private function createEvaluationConfiguration(): void
    {
        $policyConfiguration = PolicyConfiguration::query()->create([
            'code' => 'EVALUATION',
            'name' => 'Evaluation Policy',
            'description' => 'Konfigurasi kebijakan evaluasi proposal.',
            'scope' => 'global',
            'is_active' => true,
            'created_by' => $this->admin->id ?? null,
            'updated_by' => $this->admin->id ?? null,
        ]);

        $policyVersion = PolicyVersion::query()->create([
            'policy_configuration_id' => $policyConfiguration->id,
            'version_number' => '1.0',
            'configuration_data' => [
                'source' => 'evaluation_api_test',
            ],
            'status' => 'approved',
            'effective_from' => now()->subDay(),
            'effective_until' => null,
            'created_by' => $this->admin->id ?? null,
            'approved_by' => $this->admin->id ?? null,
            'approved_at' => now(),
            'approval_notes' => 'Approved for automated test.',
        ]);

        $criteria = EvaluationCriteria::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        foreach ($criteria as $index => $criterion) {
            EvaluationWeightConfiguration::query()->create([
                'grant_program_id' => $this->proposal->grant_program_id,
                'policy_version_id' => $policyVersion->id,
                'evaluation_criteria_id' => $criterion->id,
                'weight' => $criterion->default_weight ?? 0,
                'minimum_score' => $criterion->minimum_score,
                'maximum_score' => $criterion->maximum_score,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
