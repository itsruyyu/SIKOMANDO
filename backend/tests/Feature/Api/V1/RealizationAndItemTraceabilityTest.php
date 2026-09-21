<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProposalStatus;
use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Enums\RealizationPackageStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealizationAndItemTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemohon;

    private User $verifikator;

    private Proposal $proposal;

    private ProposalBudgetItem $budgetItem;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $superAdminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Admin', 'is_system' => true, 'is_active' => true]
        );

        $pemohonRole = Role::firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon', 'is_system' => false, 'is_active' => true]
        );

        $verifikatorRole = Role::firstOrCreate(
            ['code' => 'VERIFIKATOR'],
            ['id' => (string) Str::uuid(), 'name' => 'Verifikator', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create(['name' => 'Admin Sikomando']);
        $this->admin->roles()->sync([$superAdminRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Kelompok']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->verifikator = User::factory()->create(['name' => 'Staf Verifikator Realisasi']);
        $this->verifikator->roles()->sync([$verifikatorRole->id]);

        $grantProgram = GrantProgram::factory()->create();
        $organization = Organization::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'grant_program_id' => $grantProgram->id,
            'organization_id' => $organization->id,
            'applicant_id' => $this->pemohon->id,
            'title' => 'Pengadaan Sarana Produksi Ternak',
            'status' => ProposalStatus::IMPLEMENTATION,
        ]);

        $this->budgetItem = ProposalBudgetItem::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'Belanja Modal',
            'item_name' => 'Mesin Pencacah Pakan Ternak 5HP',
            'quantity' => 2,
            'unit' => 'unit',
            'unit_price' => 7500000,
            'subtotal' => 15000000,
        ]);
    }

    public function test_can_create_realization_package_with_auto_numbering_and_qr(): void
    {
        Sanctum::actingAs($this->pemohon);

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/realizations", [
            'package_name' => 'Paket Pengadaan Mesin dan Peralatan Tahap 1',
            'description' => 'Realisasi pembelian mesin pencacah pakan sesuai RAB',
            'target_completion_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'package_name' => 'Paket Pengadaan Mesin dan Peralatan Tahap 1',
                    'status' => RealizationPackageStatus::DRAFT->value,
                ],
            ]);

        $packageId = $response->json('data.id');
        $package = RealizationPackage::find($packageId);

        $this->assertNotNull($package);
        $this->assertStringStartsWith('PKG/', $package->package_number);

        // Assert QR identity was created
        $this->assertDatabaseHas('qr_identities', [
            'qrable_type' => RealizationPackage::class,
            'qrable_id' => $package->id,
            'qr_type' => QrType::REALIZATION_PACKAGE->value,
        ]);
    }

    public function test_can_add_realization_item_linked_to_rab_with_item_qr_and_history(): void
    {
        Sanctum::actingAs($this->pemohon);

        $package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/03/001',
            'package_name' => 'Pengadaan Alat Berat Ringan',
            'status' => RealizationPackageStatus::DRAFT,
            'created_by' => $this->pemohon->id,
        ]);

        $response = $this->postJson("/api/v1/realization-packages/{$package->id}/items", [
            'proposal_budget_item_id' => $this->budgetItem->id,
            'item_name' => 'Mesin Pencacah Pakan Honda GX200',
            'specification' => 'Mesin 6.5 HP 4 Tak, Pisau Baja 8 Blade',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 7500000,
            'serial_number' => 'SN-HND-2026-9912',
            'brand' => 'Honda',
            'supplier_name' => 'UD Maju Bersama Tani',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'location_address' => 'Gudang Sentral Kelompok Tani',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_name' => 'Mesin Pencacah Pakan Honda GX200',
                    'serial_number' => 'SN-HND-2026-9912',
                    'status' => RealizationItemStatus::CREATED->value,
                    'condition' => RealizationItemCondition::GOOD->value,
                ],
            ]);

        $itemId = $response->json('data.id');
        $item = RealizationItem::find($itemId);

        // Verify package total recalculated
        $this->assertEquals(7500000, $package->fresh()->total_amount);

        // Verify QR identity created for physical asset
        $this->assertDatabaseHas('qr_identities', [
            'qrable_type' => RealizationItem::class,
            'qrable_id' => $item->id,
            'qr_type' => QrType::REALIZATION_ITEM->value,
        ]);

        // Verify initial history recorded
        $this->assertDatabaseHas('realization_item_histories', [
            'realization_item_id' => $item->id,
            'action' => 'CREATED',
            'status' => RealizationItemStatus::CREATED->value,
        ]);
    }

    public function test_can_update_item_status_condition_and_geo_location_with_audit_trail(): void
    {
        Sanctum::actingAs($this->pemohon);

        $package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/03/002',
            'package_name' => 'Pengadaan Alat Berat Ringan',
            'status' => RealizationPackageStatus::DRAFT,
            'created_by' => $this->pemohon->id,
        ]);

        $item = RealizationItem::create([
            'realization_package_id' => $package->id,
            'item_code' => 'ITM-PKG-001-01',
            'name' => 'Pompa Air Irigasi 3 Inch',
            'item_name' => 'Pompa Air Irigasi 3 Inch',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 4500000,
            'total_amount' => 4500000,
            'total_price' => 4500000,
            'status' => RealizationItemStatus::CREATED,
            'condition' => RealizationItemCondition::GOOD,
            'created_by' => $this->pemohon->id,
        ]);

        $response = $this->putJson("/api/v1/realization-items/{$item->id}", [
            'status' => RealizationItemStatus::RECEIVED->value,
            'condition' => RealizationItemCondition::GOOD->value,
            'latitude' => -6.2150000,
            'longitude' => 106.8450000,
            'location_address' => 'Pos Poktan Blok B, Desa Sukajaya',
            'history_notes' => 'Barang telah diterima di pos poktan dari ekspedisi.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => RealizationItemStatus::RECEIVED->value,
                ],
            ]);

        // History entry must reflect transition
        $this->assertDatabaseHas('realization_item_histories', [
            'realization_item_id' => $item->id,
            'status' => RealizationItemStatus::RECEIVED->value,
            'notes' => 'Barang telah diterima di pos poktan dari ekspedisi.',
        ]);
    }

    public function test_can_record_physical_inspection_on_item(): void
    {
        Sanctum::actingAs($this->verifikator);

        $package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/03/003',
            'name' => 'Paket Alsintan',
            'package_name' => 'Paket Alsintan',
            'status' => RealizationPackageStatus::SUBMITTED,
            'created_by' => $this->pemohon->id,
        ]);

        $item = RealizationItem::create([
            'realization_package_id' => $package->id,
            'item_code' => 'ITM-PKG-003-01',
            'name' => 'Traktor Roda Dua',
            'item_name' => 'Traktor Roda Dua',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 25000000,
            'total_amount' => 25000000,
            'total_price' => 25000000,
            'status' => RealizationItemStatus::RECEIVED,
            'condition' => RealizationItemCondition::GOOD,
            'created_by' => $this->pemohon->id,
        ]);

        $response = $this->postJson("/api/v1/realization-items/{$item->id}/inspect", [
            'condition' => RealizationItemCondition::GOOD->value,
            'latitude' => -6.2200000,
            'longitude' => 106.8500000,
            'location_address' => 'Lahan Pertanian Poktan',
            'notes' => 'Pemeriksaan fisik langsung, mesin berfungsi normal.',
            'action' => 'FIELD_VERIFICATION',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => RealizationItemStatus::MONITORED->value,
                    'condition' => RealizationItemCondition::GOOD->value,
                ],
            ]);

        $this->assertDatabaseHas('realization_item_histories', [
            'realization_item_id' => $item->id,
            'action' => 'FIELD_VERIFICATION',
            'status' => RealizationItemStatus::MONITORED->value,
        ]);
    }

    public function test_package_submit_and_verification_workflow(): void
    {
        $package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/03/004',
            'name' => 'Paket Pengadaan',
            'package_name' => 'Paket Pengadaan',
            'status' => RealizationPackageStatus::DRAFT,
            'created_by' => $this->pemohon->id,
        ]);

        RealizationItem::create([
            'realization_package_id' => $package->id,
            'item_code' => 'ITM-004-1',
            'name' => 'Timbangan Digital 100kg',
            'item_name' => 'Timbangan Digital 100kg',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 1500000,
            'total_amount' => 1500000,
            'total_price' => 1500000,
            'status' => RealizationItemStatus::CREATED,
            'condition' => RealizationItemCondition::GOOD,
            'created_by' => $this->pemohon->id,
        ]);

        // 1. Submit package
        Sanctum::actingAs($this->pemohon);
        $submitRes = $this->postJson("/api/v1/realization-packages/{$package->id}/submit");
        $submitRes->assertStatus(200)
            ->assertJson([
                'data' => ['status' => RealizationPackageStatus::SUBMITTED->value],
            ]);

        // 2. Verify package
        Sanctum::actingAs($this->verifikator);
        $verifyRes = $this->postJson("/api/v1/realization-packages/{$package->id}/verify", [
            'status' => 'COMPLETED',
            'notes' => 'Semua barang realisasi telah diperiksa dan sesuai.',
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson([
                'data' => ['status' => RealizationPackageStatus::COMPLETED->value],
            ]);

        $this->assertEquals(RealizationPackageStatus::COMPLETED, $package->fresh()->status);
        $this->assertNotNull($package->fresh()->verified_at);
    }
}
