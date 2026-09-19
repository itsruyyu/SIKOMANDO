<?php

namespace Tests\Feature\Api\V1;

use App\Enums\FieldSurveyStatus;
use App\Enums\ProposalStatus;
use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Enums\RealizationPackageStatus;
use App\Models\FieldSurvey;
use App\Models\GrantProgram;
use App\Models\MonitoringRecord;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Role;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringAndFieldSurveyQrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $surveyor;

    private User $pemohon;

    private Proposal $proposal;

    private RealizationPackage $package;

    private RealizationItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $adminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Administrator', 'is_system' => true, 'is_active' => true]
        );

        $surveyorRole = Role::firstOrCreate(
            ['code' => 'SURVEYOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Surveyor', 'is_system' => false, 'is_active' => true]
        );

        $pemohonRole = Role::firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create(['name' => 'Admin Sikomando']);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->surveyor = User::factory()->create(['name' => 'Petugas Lapangan']);
        $this->surveyor->roles()->sync([$surveyorRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Penerima']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $program = GrantProgram::factory()->create([
            'fiscal_year' => 2026,
            'status' => 'active',
        ]);

        $org = Organization::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'grant_program_id' => $program->id,
            'organization_id' => $org->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::DISBURSED,
            'proposal_number' => 'PROP-2026-MNT-001',
        ]);

        $budgetItem = ProposalBudgetItem::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'equipment',
            'item_name' => 'Pompa Air Irigasi 3 Inch',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 15000000,
            'subtotal' => 15000000,
        ]);

        $this->package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/0010',
            'name' => 'Paket Mesin Pompa',
            'total_amount' => 15000000,
            'status' => RealizationPackageStatus::VERIFIED,
            'created_by' => $this->admin->id,
        ]);

        $this->item = RealizationItem::create([
            'realization_package_id' => $this->package->id,
            'proposal_budget_item_id' => $budgetItem->id,
            'item_code' => 'ITM/2026/0010',
            'item_name' => 'Pompa Air Honda GX160',
            'serial_number' => 'SN-PMP-7788',
            'unit' => 'unit',
            'quantity' => 1,
            'unit_price' => 15000000,
            'total_price' => 15000000,
            'condition' => RealizationItemCondition::GOOD,
            'status' => RealizationItemStatus::ASSIGNED,
            'created_by' => $this->admin->id,
        ]);

        // Generate active QR for realization item
        app(QrService::class)->generateFor(
            entity: $this->item,
            type: QrType::REALIZATION_ITEM,
            actor: $this->admin,
            metadata: ['serial_number' => 'SN-PMP-7788']
        );
    }

    public function test_can_create_monitoring_record_with_qr(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'title' => 'Monitoring Triwulan I Realisasi Pompa Air',
            'monitoring_date' => '2026-09-18',
            'notes' => 'Pemeriksaan fisik sarana di lokasi kelompok',
        ];

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/monitoring-records", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $recordId = $response->json('data.id');
        $record = MonitoringRecord::with('qrIdentity')->findOrFail($recordId);

        $this->assertStringStartsWith('MONI/', $record->monitoring_number);
        $this->assertNotNull($record->qrIdentity);
        $this->assertEquals(QrType::MONITORING, $record->qrIdentity->type);
        $this->assertEquals(QrStatus::ACTIVE, $record->qrIdentity->status);
    }

    public function test_can_scan_and_check_item_in_monitoring_via_qr_token(): void
    {
        Sanctum::actingAs($this->admin);

        $record = MonitoringRecord::create([
            'proposal_id' => $this->proposal->id,
            'monitoring_number' => 'MNT/2026/0001',
            'monitoring_type' => 'periodic',
            'created_by' => $this->admin->id,
            'status' => 'in_progress',
            'summary' => 'Inspeksi Pompa',
        ]);

        $itemQr = $this->item->qrIdentity;
        $this->assertNotNull($itemQr);

        $response = $this->postJson("/api/v1/monitoring-records/{$record->id}/check-item", [
            'token' => $itemQr->token,
            'condition' => 'GOOD',
            'latitude' => 0.5432,
            'longitude' => 123.0567,
            'notes' => 'Mesin berfungsi normal saat dites',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.realization_item_id', $this->item->id)
            ->assertJsonPath('data.scanned_qr_token', $itemQr->token);

        // Verification log recorded
        $this->assertDatabaseHas('qr_verification_logs', [
            'qr_identity_id' => $itemQr->id,
            'scan_context' => 'monitoring',
        ]);
    }

    public function test_can_complete_monitoring_record(): void
    {
        Sanctum::actingAs($this->admin);

        $record = MonitoringRecord::create([
            'proposal_id' => $this->proposal->id,
            'monitoring_number' => 'MNT/2026/0002',
            'monitoring_type' => 'periodic',
            'created_by' => $this->admin->id,
            'status' => 'in_progress',
            'summary' => 'Monitoring Akhir',
        ]);

        $response = $this->postJson("/api/v1/monitoring-records/{$record->id}/complete", [
            'overall_result' => 'SATISFACTORY',
            'notes' => 'Pemanfaatan bantuan telah sesuai peruntukan',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals('completed', $record->fresh()->status);
    }

    public function test_field_survey_can_record_finding_with_scanned_realization_qr(): void
    {
        Sanctum::actingAs($this->surveyor);

        $survey = FieldSurvey::create([
            'proposal_id' => $this->proposal->id,
            'survey_number' => 'SRV/2026/0001',
            'surveyor_id' => $this->surveyor->id,
            'scheduled_date' => now()->toDateString(),
            'location_name' => 'Lahan Pertanian Kelompok',
            'status' => FieldSurveyStatus::IN_PROGRESS,
            'created_by' => $this->admin->id,
        ]);

        $itemQr = $this->item->qrIdentity;
        $this->assertNotNull($itemQr);

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/field-surveys/{$survey->id}/findings", [
            'title' => 'Verifikasi Lokasi Penempatan Pompa Air',
            'description' => 'Pompa terpasang dan beroperasi di rumah pompa kelompok.',
            'severity' => 'low',
            'realization_item_id' => $this->item->id,
            'scanned_qr_token' => $itemQr->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.realization_item_id', $this->item->id)
            ->assertJsonPath('data.scanned_qr_token', $itemQr->token);

        $this->assertDatabaseHas('field_survey_findings', [
            'field_survey_id' => $survey->id,
            'realization_item_id' => $this->item->id,
            'scanned_qr_token' => $itemQr->token,
        ]);
    }
}
