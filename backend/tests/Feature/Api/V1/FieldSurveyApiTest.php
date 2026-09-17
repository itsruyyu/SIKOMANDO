<?php

namespace Tests\Feature\Api\V1;

use App\Enums\FieldSurveyFindingSeverity;
use App\Enums\FieldSurveyItemResult;
use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Enums\ProposalStatus;
use App\Models\FieldSurvey;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FieldSurveyApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $surveyor;

    private User $otherSurveyor;

    private User $auditor;

    private User $pemohon;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Administrator',
                'description' => 'Akses penuh.',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $surveyorRole = Role::query()->firstOrCreate(
            ['code' => 'SURVEYOR'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Surveyor',
                'description' => 'Petugas survei lapangan.',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $auditorRole = Role::query()->firstOrCreate(
            ['code' => 'AUDITOR'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Auditor',
                'description' => 'Petugas pemeriksa dan audit.',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $pemohonRole = Role::query()->firstOrCreate(
            ['code' => 'PEMOHON'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pemohon',
                'description' => 'Pengguna pemohon hibah.',
                'is_system' => false,
                'is_active' => true,
            ]
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->sync([$adminRole->id]);

        $this->surveyor = User::factory()->create();
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->otherSurveyor = User::factory()->create();
        $this->otherSurveyor->roles()->sync([$surveyorRole->id]);

        $this->auditor = User::factory()->create();
        $this->auditor->roles()->sync([$auditorRole->id]);

        $this->pemohon = User::factory()->create();
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->proposal = Proposal::factory()->create([
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::EVALUATION,
        ]);
    }

    public function test_admin_can_create_field_survey_for_eligible_proposal(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys",
            [
                'surveyor_id' => $this->surveyor->id,
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'location_name' => 'Sekretariat Yayasan Peduli Mandiri',
                'location_address' => 'Jl. Merdeka No. 123, Kota Gorontalo',
                'latitude' => 0.5401,
                'longitude' => 123.0601,
                'notes' => 'Survei awal kelayakan objek bantuan hibah.',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.proposal_id', $this->proposal->id)
            ->assertJsonPath('data.surveyor_id', $this->surveyor->id)
            ->assertJsonPath('data.status', FieldSurveyStatus::ASSIGNED->value);

        $surveyId = $response->json('data.id');

        $this->assertDatabaseHas('field_surveys', [
            'id' => $surveyId,
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::ASSIGNED->value,
        ]);

        // Default 4 checklist items should have been generated
        $this->assertDatabaseCount('field_survey_items', 4);
        $this->assertDatabaseHas('field_survey_items', [
            'field_survey_id' => $surveyId,
            'item_code' => 'SRV-LOC',
            'result' => FieldSurveyItemResult::PENDING->value,
        ]);

        // Proposal status should transition to SURVEY
        $this->proposal->refresh();
        $this->assertEquals(ProposalStatus::SURVEY, $this->proposal->status);

        // Audit trail recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.created',
            'entity_id' => $surveyId,
        ]);
    }

    public function test_cannot_create_field_survey_for_ineligible_proposal(): void
    {
        Sanctum::actingAs($this->admin);

        $draftProposal = Proposal::factory()->create([
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::DRAFT,
        ]);

        $response = $this->postJson(
            "/api/v1/proposals/{$draftProposal->id}/field-surveys",
            [
                'surveyor_id' => $this->surveyor->id,
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['proposal']);
    }

    public function test_cannot_create_duplicate_active_field_survey_on_same_proposal(): void
    {
        Sanctum::actingAs($this->admin);

        FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::ASSIGNED,
        ]);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys",
            [
                'surveyor_id' => $this->surveyor->id,
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['survey']);
    }

    public function test_non_admin_cannot_create_field_survey(): void
    {
        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys",
            [
                'surveyor_id' => $this->surveyor->id,
            ]
        );

        $response->assertForbidden();
    }

    public function test_surveyor_can_view_assigned_surveys_list(): void
    {
        FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $anotherProposal = Proposal::factory()->create(['status' => ProposalStatus::SURVEY]);
        FieldSurvey::factory()->create([
            'proposal_id' => $anotherProposal->id,
            'surveyor_id' => $this->otherSurveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->getJson('/api/v1/field-surveys/assigned');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.surveyor_id', $this->surveyor->id);
    }

    public function test_surveyor_can_view_own_survey_details(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $survey->id)
            ->assertJsonCount(4, 'data.items');
    }

    public function test_surveyor_cannot_view_other_surveyors_survey(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->otherSurveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );

        $response->assertForbidden();
    }

    public function test_pemohon_cannot_access_field_surveys(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->pemohon);

        $listResponse = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys"
        );
        $listResponse->assertForbidden();

        $detailResponse = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );
        $detailResponse->assertForbidden();
    }

    public function test_auditor_can_view_any_field_survey(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->auditor);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $survey->id);
    }

    public function test_nested_route_mismatch_returns_404(): void
    {
        $otherProposal = Proposal::factory()->create(['status' => ProposalStatus::SURVEY]);
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $otherProposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );

        $response->assertNotFound();
    }

    public function test_surveyor_can_update_schedule_and_location(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($this->surveyor);

        $newDate = now()->addDays(5)->toDateString();
        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/schedule",
            [
                'scheduled_date' => $newDate,
                'location_name' => 'Kantor Cabang Baru',
                'location_address' => 'Jl. Sam Ratulangi No. 45',
                'latitude' => 0.5512,
                'longitude' => 123.0789,
                'notes' => 'Jadwal disepakati bersama pengurus.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::SCHEDULED->value)
            ->assertJsonPath('data.scheduled_date', $newDate)
            ->assertJsonPath('data.location_name', 'Kantor Cabang Baru');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.scheduled',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_surveyor_can_start_survey(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::SCHEDULED,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/start"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::IN_PROGRESS->value);

        $this->assertDatabaseHas('field_surveys', [
            'id' => $survey->id,
            'status' => FieldSurveyStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.started',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_surveyor_can_update_checklist_item(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $item = $survey->items()->firstOrFail();

        Sanctum::actingAs($this->surveyor);

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/items/{$item->id}",
            [
                'result' => 'pass',
                'notes' => 'Lokasi fisik terbukti ada dan aktif.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result', 'pass')
            ->assertJsonPath('data.notes', 'Lokasi fisik terbukti ada dan aktif.')
            ->assertJsonPath('data.checked_by', $this->surveyor->id);

        $this->assertDatabaseHas('field_survey_items', [
            'id' => $item->id,
            'result' => 'pass',
            'checked_by' => $this->surveyor->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.item_updated',
            'entity_id' => $item->id,
        ]);
    }

    public function test_surveyor_cannot_update_item_of_different_survey(): void
    {
        $survey1 = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $anotherProposal = Proposal::factory()->create(['status' => ProposalStatus::SURVEY]);
        $survey2 = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $anotherProposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $itemFromSurvey2 = $survey2->items()->firstOrFail();

        Sanctum::actingAs($this->surveyor);

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey1->id}/items/{$itemFromSurvey2->id}",
            [
                'result' => 'pass',
            ]
        );

        $response->assertNotFound();
    }

    public function test_surveyor_can_add_finding(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/findings",
            [
                'title' => 'Plang nama belum terpasang permanen',
                'description' => 'Plang nama yayasan masih berupa spanduk sementara.',
                'severity' => 'low',
                'finding_type' => 'facility',
                'recommended_action' => 'Memasang plang nama permanen sebelum pencairan termin kedua.',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Plang nama belum terpasang permanen')
            ->assertJsonPath('data.severity', FieldSurveyFindingSeverity::LOW->value);

        $findingId = $response->json('data.id');

        $this->assertDatabaseHas('field_survey_findings', [
            'id' => $findingId,
            'field_survey_id' => $survey->id,
            'severity' => FieldSurveyFindingSeverity::LOW->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.finding_created',
            'entity_id' => $findingId,
        ]);
    }

    public function test_surveyor_can_add_document(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/documents",
            [
                'document_title' => 'Foto Tampak Depan Sekretariat',
                'original_filename' => 'tampak_depan.jpg',
                'storage_path' => 'surveys/photos/tampak_depan_123.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 204800,
                'notes' => 'Dokumentasi foto tampak depan.',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document_title', 'Foto Tampak Depan Sekretariat')
            ->assertJsonPath('data.uploaded_by', $this->surveyor->id);

        $docId = $response->json('data.id');

        $this->assertDatabaseHas('field_survey_documents', [
            'id' => $docId,
            'field_survey_id' => $survey->id,
            'original_filename' => 'tampak_depan.jpg',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.document_uploaded',
            'entity_id' => $docId,
        ]);
    }

    public function test_surveyor_can_fill_survey_result(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/result",
            [
                'summary' => 'Kondisi fisik dan kegiatan telah sesuai dengan proposal.',
                'recommendation' => 'Direkomendasikan menerima bantuan dengan pendampingan teknis.',
                'notes' => 'Telah dilakukan musyawarah dengan pemohon.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary', 'Kondisi fisik dan kegiatan telah sesuai dengan proposal.')
            ->assertJsonPath('data.recommendation', 'Direkomendasikan menerima bantuan dengan pendampingan teknis.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.result_updated',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_surveyor_cannot_submit_if_items_are_still_pending(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'summary' => 'Sudah ada ringkasan.',
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/submit"
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_surveyor_cannot_submit_if_summary_is_empty(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'summary' => null,
        ]);

        // Rate all items
        foreach ($survey->items as $item) {
            $item->update(['result' => FieldSurveyItemResult::PASS]);
        }

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/submit"
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['summary']);
    }

    public function test_surveyor_can_submit_completed_survey(): void
    {
        $survey = FieldSurvey::factory()->inProgress()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'summary' => 'Survei lengkap terverifikasi.',
        ]);

        foreach ($survey->items as $item) {
            $item->update(['result' => FieldSurveyItemResult::PASS]);
        }

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/submit",
            ['notes' => 'Pengajuan berkas survei selesai.']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::SUBMITTED->value);

        $this->assertDatabaseHas('field_surveys', [
            'id' => $survey->id,
            'status' => FieldSurveyStatus::SUBMITTED->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.submitted',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_assigned_surveyor_cannot_review_own_survey(): void
    {
        $survey = FieldSurvey::factory()->submitted()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->surveyor);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/review",
            [
                'action' => 'reviewed',
            ]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_review_and_request_revision(): void
    {
        $survey = FieldSurvey::factory()->submitted()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/review",
            [
                'action' => 'request_revision',
                'notes' => 'Mohon tambahkan dokumentasi foto detail lokasi kegiatan.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::REVISION_REQUIRED->value);

        $this->assertDatabaseHas('field_surveys', [
            'id' => $survey->id,
            'status' => FieldSurveyStatus::REVISION_REQUIRED->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.revision_requested',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_admin_can_complete_survey_with_recommended_result(): void
    {
        $survey = FieldSurvey::factory()->submitted()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        // Proposal is in SURVEY status
        $this->proposal->update(['status' => ProposalStatus::SURVEY]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/complete",
            [
                'result' => 'recommended',
                'summary' => 'Survei valid dan layak disetujui.',
                'recommendation' => 'Disetujui untuk proses rekomendasi bantuan.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::COMPLETED->value)
            ->assertJsonPath('data.result', FieldSurveyResult::RECOMMENDED->value);

        $this->assertDatabaseHas('field_surveys', [
            'id' => $survey->id,
            'status' => FieldSurveyStatus::COMPLETED->value,
            'result' => FieldSurveyResult::RECOMMENDED->value,
        ]);

        // Proposal must transition to RECOMMENDED
        $this->proposal->refresh();
        $this->assertEquals(ProposalStatus::RECOMMENDED, $this->proposal->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.completed',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_admin_can_complete_survey_with_not_recommended_result(): void
    {
        $survey = FieldSurvey::factory()->submitted()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $this->proposal->update(['status' => ProposalStatus::SURVEY]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/complete",
            [
                'result' => 'not_recommended',
                'summary' => 'Objek tidak ditemukan di lokasi dan kepengurusan fiktif.',
                'recommendation' => 'Ditolak total.',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', FieldSurveyStatus::REJECTED->value)
            ->assertJsonPath('data.result', FieldSurveyResult::NOT_RECOMMENDED->value);

        $this->assertDatabaseHas('field_surveys', [
            'id' => $survey->id,
            'status' => FieldSurveyStatus::REJECTED->value,
            'result' => FieldSurveyResult::NOT_RECOMMENDED->value,
        ]);

        // Proposal must transition to REJECTED
        $this->proposal->refresh();
        $this->assertEquals(ProposalStatus::REJECTED, $this->proposal->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.rejected',
            'entity_id' => $survey->id,
        ]);
    }

    public function test_cannot_mutate_or_delete_completed_survey(): void
    {
        $survey = FieldSurvey::factory()->completed()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
        ]);

        $item = $survey->items()->firstOrFail();

        Sanctum::actingAs($this->surveyor);

        // Cannot update item
        $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/items/{$item->id}",
            ['result' => 'fail']
        )->assertForbidden();

        // Cannot update schedule
        $this->patchJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/schedule",
            ['scheduled_date' => now()->toDateString()]
        )->assertForbidden();

        // Surveyor cannot delete survey
        $this->deleteJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        )->assertForbidden();

        // Admin cannot delete final survey (service validation error 422)
        Sanctum::actingAs($this->admin);
        $this->deleteJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['survey']);
    }

    public function test_admin_can_delete_non_final_field_survey(): void
    {
        $survey = FieldSurvey::factory()->create([
            'proposal_id' => $this->proposal->id,
            'surveyor_id' => $this->surveyor->id,
            'status' => FieldSurveyStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson(
            "/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('field_surveys', [
            'id' => $survey->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'field_survey.deleted',
            'entity_id' => $survey->id,
        ]);
    }
}
