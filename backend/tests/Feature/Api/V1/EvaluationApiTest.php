<?php

namespace Tests\Feature\Api\V1;

use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
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

        $this->evaluator = User::factory()->create();

        $this->evaluator->roles()->syncWithoutDetaching([
            $evaluatorRole->id,
        ]);

        $this->proposal = Proposal::factory()->create([
            'applicant_id' => $this->evaluator->id,
        ]);

        EvaluationCriteria::query()->create([
            'code' => 'CRITERIA-' . Str::upper(Str::random(8)),
            'name' => 'Kelayakan Program',
            'description' => 'Penilaian kelayakan program.',
            'criterion_type' => 'score',
            'default_weight' => 50,
            'minimum_score' => 0,
            'maximum_score' => 100,
            'is_active' => true,
        ]);

        EvaluationCriteria::query()->create([
            'code' => 'CRITERIA-' . Str::upper(Str::random(8)),
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
}