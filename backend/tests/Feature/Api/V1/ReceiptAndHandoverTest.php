<?php

namespace Tests\Feature\Api\V1;

use App\Enums\HandoverStatus;
use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Enums\RealizationPackageStatus;
use App\Enums\ReceiptStatus;
use App\Models\GrantProgram;
use App\Models\Handover;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptAndHandoverTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemohon;

    private Proposal $proposal;

    private RealizationPackage $package;

    private RealizationItem $item1;

    private RealizationItem $item2;

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

        $this->admin = User::factory()->create(['name' => 'Admin Sikomando']);
        $this->admin->roles()->sync([$superAdminRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Ketua Kelompok']);
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
            'status' => 'disbursed',
            'proposal_number' => 'PROP-2026-TEST-001',
        ]);

        $budgetItem = ProposalBudgetItem::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'equipment',
            'item_name' => 'Traktor Roda 4',
            'quantity' => 2,
            'unit' => 'unit',
            'unit_price' => 50000000,
            'subtotal' => 100000000,
        ]);

        $this->package = RealizationPackage::create([
            'proposal_id' => $this->proposal->id,
            'package_number' => 'PKG/2026/0001',
            'package_name' => 'Pengadaan Traktor',
            'total_amount' => 100000000,
            'realization_date' => now()->toDateString(),
            'status' => RealizationPackageStatus::VERIFIED,
            'created_by' => $this->admin->id,
        ]);

        $this->item1 = RealizationItem::create([
            'realization_package_id' => $this->package->id,
            'proposal_budget_item_id' => $budgetItem->id,
            'item_code' => 'ITM/2026/0001',
            'item_name' => 'Traktor Unit A',
            'serial_number' => 'TRK-SN-001',
            'unit' => 'unit',
            'quantity' => 1,
            'unit_price' => 50000000,
            'total_price' => 50000000,
            'condition' => RealizationItemCondition::GOOD,
            'status' => RealizationItemStatus::RECEIVED,
            'created_by' => $this->admin->id,
        ]);

        $this->item2 = RealizationItem::create([
            'realization_package_id' => $this->package->id,
            'proposal_budget_item_id' => $budgetItem->id,
            'item_code' => 'ITM/2026/0002',
            'item_name' => 'Traktor Unit B',
            'serial_number' => 'TRK-SN-002',
            'unit' => 'unit',
            'quantity' => 1,
            'unit_price' => 50000000,
            'total_price' => 50000000,
            'condition' => RealizationItemCondition::GOOD,
            'status' => RealizationItemStatus::RECEIVED,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_can_create_receipt_with_auto_generated_qr(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'realization_package_id' => $this->package->id,
            'amount' => 50000000,
            'receipt_date' => '2026-09-18',
            'paid_to' => 'PT Sumber Mesin Pertanian',
            'payment_method' => 'transfer',
            'description' => 'Pembayaran termin 1 Traktor Roda 4',
        ];

        $response = $this->postJson("/api/v1/proposals/{$this->proposal->id}/receipts", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recipient_name', 'PT Sumber Mesin Pertanian');

        $receiptId = $response->json('data.id');
        $receipt = Receipt::with('qrIdentity')->findOrFail($receiptId);

        $this->assertStringStartsWith('RCP/', $receipt->receipt_number);
        $this->assertNotNull($receipt->qrIdentity);
        $this->assertEquals(QrType::RECEIPT, $receipt->qrIdentity->type);
        $this->assertEquals(QrStatus::ACTIVE, $receipt->qrIdentity->status);
        $this->assertNotEmpty($receipt->qrIdentity->verification_url);
    }

    public function test_can_update_and_verify_receipt(): void
    {
        Sanctum::actingAs($this->admin);

        $receipt = Receipt::create([
            'proposal_id' => $this->proposal->id,
            'realization_package_id' => $this->package->id,
            'receipt_number' => 'RCP/2026/TEST-002',
            'payer_name' => 'Pemprov',
            'recipient_name' => 'PT Toko Mesin',
            'amount' => 25000000,
            'receipt_date' => '2026-09-18',
            'purpose' => 'Pembayaran Uang Muka',
            'status' => ReceiptStatus::ISSUED,
            'created_by' => $this->admin->id,
        ]);

        // Update
        $updateResponse = $this->putJson("/api/v1/receipts/{$receipt->id}", [
            'amount' => 30000000,
            'description' => 'Pembayaran Uang Muka Revisi',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.amount', '30000000.00');

        // Verify
        $verifyResponse = $this->postJson("/api/v1/receipts/{$receipt->id}/verify", [
            'notes' => 'Telah dicocokkan dengan rekening koran',
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'verified');

        $this->assertEquals(ReceiptStatus::VERIFIED, $receipt->fresh()->status);
    }

    public function test_cancel_receipt_revokes_qr_identity(): void
    {
        Sanctum::actingAs($this->admin);

        $createResponse = $this->postJson("/api/v1/proposals/{$this->proposal->id}/receipts", [
            'amount' => 10000000,
            'paid_to' => 'CV Bengkel Abadi',
            'description' => 'Servis awal',
        ]);

        $createResponse->assertStatus(201);
        $receiptId = $createResponse->json('data.id');
        $receipt = Receipt::findOrFail($receiptId);
        $qrId = $receipt->qrIdentity->id;

        // Cancel receipt
        $cancelResponse = $this->postJson("/api/v1/receipts/{$receiptId}/cancel", [
            'reason' => 'Transaksi salah input nominal rekening tujuan',
        ]);

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $receipt->refresh();
        $this->assertEquals(ReceiptStatus::CANCELLED, $receipt->status);

        // Historical QR check
        $historicQr = $receipt->qrIdentities()->where('id', $qrId)->first();
        $this->assertNotNull($historicQr);
        $this->assertEquals(QrStatus::REVOKED, $historicQr->status);
    }

    public function test_can_create_handover_with_items_and_qr(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'handover_by_name' => 'Drs. Budi Santoso',
            'handover_by_position' => 'Pejabat Pembuat Komitmen',
            'handover_to_name' => 'Ahmad Dahlan',
            'handover_to_position' => 'Ketua Kelompok Tani Makmur',
            'location' => 'Kantor Dinas Pertanian Kabupaten',
            'notes' => 'Serah terima 2 unit traktor dalam kondisi baru',
            'items' => [
                ['realization_item_id' => $this->item1->id, 'notes' => 'Lengkap dengan garansi'],
                ['realization_item_id' => $this->item2->id, 'notes' => 'Lengkap dengan garansi'],
            ],
        ];

        $response = $this->postJson("/api/v1/realization-packages/{$this->package->id}/handovers", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recipient_name', 'Ahmad Dahlan');

        $handoverId = $response->json('data.id');
        $handover = Handover::with(['qrIdentity', 'items'])->findOrFail($handoverId);

        $this->assertStringStartsWith('BAST/', $handover->handover_number);
        $this->assertCount(2, $handover->items);
        $this->assertNotNull($handover->qrIdentity);
        $this->assertEquals(QrType::HANDOVER, $handover->qrIdentity->type);
        $this->assertEquals(QrStatus::ACTIVE, $handover->qrIdentity->status);
    }

    public function test_can_complete_handover_and_transition_item_status(): void
    {
        Sanctum::actingAs($this->admin);

        $handover = Handover::create([
            'handover_number' => 'BAST/2026/0002',
            'proposal_id' => $this->proposal->id,
            'realization_package_id' => $this->package->id,
            'giver_name' => 'Budi Santoso',
            'recipient_name' => 'Ahmad Dahlan',
            'status' => HandoverStatus::DRAFT,
            'created_by' => $this->admin->id,
            'handover_date' => now()->toDateString(),
        ]);

        $handover->items()->attach([$this->item1->id, $this->item2->id]);

        $response = $this->postJson("/api/v1/handovers/{$handover->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals(RealizationItemStatus::ASSIGNED, $this->item1->fresh()->status);
        $this->assertEquals(RealizationItemStatus::ASSIGNED, $this->item2->fresh()->status);
    }

    public function test_cancel_handover_revokes_qr_identity(): void
    {
        Sanctum::actingAs($this->admin);

        $createResponse = $this->postJson("/api/v1/realization-packages/{$this->package->id}/handovers", [
            'handover_by_name' => 'Budi Santoso',
            'handover_to_name' => 'Ahmad Dahlan',
        ]);

        $createResponse->assertStatus(201);
        $handoverId = $createResponse->json('data.id');
        $handover = Handover::findOrFail($handoverId);
        $qrId = $handover->qrIdentity->id;

        $cancelResponse = $this->postJson("/api/v1/handovers/{$handoverId}/cancel", [
            'reason' => 'Penerima berhalangan dan data BAST akan diterbitkan ulang',
        ]);

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $historicQr = $handover->qrIdentities()->where('id', $qrId)->first();
        $this->assertNotNull($historicQr);
        $this->assertEquals(QrStatus::REVOKED, $historicQr->status);
    }
}
