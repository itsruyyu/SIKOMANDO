<?php

namespace Tests\Feature\Api\V1;

use App\Enums\HandoverStatus;
use App\Enums\ProposalStatus;
use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Enums\RealizationPackageStatus;
use App\Enums\ReceiptStatus;
use App\Models\Decision;
use App\Models\GrantProgram;
use App\Models\Handover;
use App\Models\MonitoringRecord;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use App\Models\RealizationItem;
use App\Models\RealizationPackage;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\SignatureProfile;
use App\Models\User;
use App\Services\DigitalSignatureService;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FullTraceabilityE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $official;

    private User $pemohon;

    private GrantProgram $program;

    private Organization $organization;

    private Proposal $proposal;

    private ProposalBudgetItem $budgetItem;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $adminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Administrator', 'is_system' => true, 'is_active' => true]
        );

        $officialRole = Role::firstOrCreate(
            ['code' => 'APPROVER'],
            ['id' => (string) Str::uuid(), 'name' => 'Approver', 'is_system' => false, 'is_active' => true]
        );

        $pemohonRole = Role::firstOrCreate(
            ['code' => 'PEMOHON'],
            ['id' => (string) Str::uuid(), 'name' => 'Pemohon Hibah', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create(['name' => 'Admin SI-KOMANDO']);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->official = User::factory()->create(['name' => 'Dr. H. Rusli Habibie, M.Si']);
        $this->official->roles()->sync([$officialRole->id]);

        $this->pemohon = User::factory()->create(['name' => 'Kaharuddin Umar']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $this->program = GrantProgram::factory()->create([
            'name' => 'Program Bantuan Modernisasi Pertanian 2026',
            'fiscal_year' => 2026,
            'status' => 'active',
        ]);

        $this->organization = Organization::factory()->create([
            'name' => 'Kelompok Tani Harapan Jaya',
        ]);

        $this->proposal = Proposal::factory()->create([
            'grant_program_id' => $this->program->id,
            'organization_id' => $this->organization->id,
            'applicant_id' => $this->pemohon->id,
            'status' => ProposalStatus::DISBURSED,
            'proposal_number' => 'PROP-2026-E2E-999',
            'requested_amount' => 120000000,
            'approved_amount' => 120000000,
        ]);

        $this->budgetItem = ProposalBudgetItem::create([
            'proposal_id' => $this->proposal->id,
            'category' => 'machinery',
            'item_name' => 'Mesin Combine Harvester Mini',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 120000000,
            'subtotal' => 120000000,
        ]);
    }

    public function test_complete_end_to_end_traceability_lifecycle_and_public_verification(): void
    {
        // -------------------------------------------------------------
        // Step 1: Sign Decision Letter (SK) with Digital Signature
        // -------------------------------------------------------------
        Sanctum::actingAs($this->official);

        $profile = app(DigitalSignatureService::class)->createProfile($this->admin, [
            'user_id' => $this->official->id,
            'name' => $this->official->name,
            'position' => 'Gubernur Gorontalo',
            'nip' => '196305171989031005',
            'authority_level' => 'head_of_agency',
            'status' => 'active',
        ]);

        $decision = Decision::create([
            'proposal_id' => $this->proposal->id,
            'decision_number' => 'SK/2026/GUB/0888',
            'status' => \App\Enums\DecisionStatus::PUBLISHED,
            'result' => \App\Enums\DecisionResult::APPROVED,
            'issued_by' => $this->official->id,
            'decision_date' => now()->toDateString(),
            'approved_amount' => 120000000,
            'title' => 'SK Penetapan Penerima Hibah Combine Harvester',
        ]);

        $fakeDocPath = 'documents/sk_gub_0888.pdf';
        Storage::disk('public')->put($fakeDocPath, '%PDF-1.4 Mock SK Document Content');

        $sigRequest = app(DigitalSignatureService::class)->requestSignature(
            signable: $decision,
            signer: $this->official,
            requester: $this->admin,
            documentPath: $fakeDocPath,
            notes: 'Persetujuan dan Pengesahan SK Penerima Hibah'
        );

        $digitalSig = app(DigitalSignatureService::class)->sign(
            signature: $sigRequest,
            actor: $this->official
        );

        $this->assertNotNull($decision->qrIdentity);
        $skQrToken = $decision->qrIdentity->token;
        $this->assertNotEmpty($skQrToken);

        // -------------------------------------------------------------
        // Step 2: Create Realization Package & Realization Item with QR
        // -------------------------------------------------------------
        Sanctum::actingAs($this->admin);

        $packageResponse = $this->postJson("/api/v1/proposals/{$this->proposal->id}/realizations", [
            'package_name' => 'Pengadaan Combine Harvester Mini',
            'description' => 'Realisasi pembelian combine harvester dari distributor resmi',
            'target_completion_date' => now()->addDays(20)->toDateString(),
        ]);

        $packageResponse->assertStatus(201);
        $packageId = $packageResponse->json('data.id');
        $package = RealizationPackage::findOrFail($packageId);
        $this->assertNotNull($package->qrIdentity);

        // Add realization item linked to ProposalBudgetItem
        $itemResponse = $this->postJson("/api/v1/realization-packages/{$packageId}/items", [
            'proposal_budget_item_id' => $this->budgetItem->id,
            'item_name' => 'Combine Harvester Kubota DC-35',
            'serial_number' => 'KUB-DC35-889900',
            'quantity' => 1,
            'unit' => 'unit',
            'unit_price' => 120000000,
            'condition' => 'good',
            'location_address' => 'Gudang Dinas Pertanian Provinsi',
            'latitude' => 0.5367,
            'longitude' => 123.0644,
        ]);

        $itemResponse->assertStatus(201);
        $itemId = $itemResponse->json('data.id');
        $item = RealizationItem::with('qrIdentity')->findOrFail($itemId);
        $this->assertNotNull($item->qrIdentity);
        $itemQrToken = $item->qrIdentity->token;

        // -------------------------------------------------------------
        // Step 3: Create & Verify Payment Receipt
        // -------------------------------------------------------------
        Sanctum::actingAs($this->pemohon);
        $receiptResponse = $this->postJson("/api/v1/proposals/{$this->proposal->id}/receipts", [
            'realization_package_id' => $package->id,
            'amount' => 120000000,
            'receipt_date' => now()->toDateString(),
            'paid_to' => 'PT Kubota Indonesia Raya',
            'description' => 'Pembayaran lunas 1 unit combine harvester mini',
        ]);

        $receiptResponse->assertStatus(201);
        $receiptId = $receiptResponse->json('data.id');
        $receipt = Receipt::with('qrIdentity')->findOrFail($receiptId);
        $receiptQrToken = $receipt->qrIdentity->token;

        // Verify receipt
        // Verify receipt by Admin (Maker-Checker: Verifier must be distinct from Creator)
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/receipts/{$receiptId}/verify")->assertStatus(200);

        // -------------------------------------------------------------
        // Step 4: BAST Handover (Serah Terima) and Item Transition
        // -------------------------------------------------------------
        $handoverResponse = $this->postJson("/api/v1/realization-packages/{$packageId}/handovers", [
            'handover_by_name' => 'Pejabat Pembuat Komitmen (PPK)',
            'handover_to_name' => 'Kaharuddin Umar (Ketua Poktan)',
            'location' => 'Desa Makmur Sejahtera, Gorontalo',
            'items' => [
                ['realization_item_id' => $item->id, 'notes' => 'Diserahterimakan dalam kondisi baru & berfungsi prima'],
            ],
        ]);

        $handoverResponse->assertStatus(201);
        $handoverId = $handoverResponse->json('data.id');
        $handover = Handover::with(['qrIdentity', 'items'])->findOrFail($handoverId);
        $handoverQrToken = $handover->qrIdentity->token;

        // Complete handover -> items become ASSIGNED
        $this->postJson("/api/v1/handovers/{$handoverId}/complete")->assertStatus(200);
        $this->assertEquals(RealizationItemStatus::ASSIGNED, $item->fresh()->status);

        // -------------------------------------------------------------
        // Step 5: Monitoring Record & Physical Verification Scan
        // -------------------------------------------------------------
        $monitoringResponse = $this->postJson("/api/v1/proposals/{$this->proposal->id}/monitoring-records", [
            'title' => 'Monitoring Operasional Alat Pertanian',
            'monitoring_date' => now()->toDateString(),
        ]);

        $monitoringResponse->assertStatus(201);
        $monitoringId = $monitoringResponse->json('data.id');

        // Scan item QR in field during monitoring
        $checkResponse = $this->postJson("/api/v1/monitoring-records/{$monitoringId}/check-item", [
            'token' => $itemQrToken,
            'condition' => 'good',
            'latitude' => 0.5401,
            'longitude' => 123.0722,
            'notes' => 'Alat sedang digunakan panen padi di sawah anggota',
        ]);

        $checkResponse->assertStatus(201);

        // Complete monitoring
        $this->postJson("/api/v1/monitoring-records/{$monitoringId}/complete", [
            'overall_result' => 'SATISFACTORY',
        ])->assertStatus(200);

        // -------------------------------------------------------------
        // Step 6: Public Verification API (Anonymous Citizen Checks)
        // -------------------------------------------------------------
        // Public must not need any auth token
        Sanctum::actingAs(new User); // Clear auth

        // 6.1 Verify SK Document QR
        $pubSk = $this->getJson("/api/v1/public/verify/{$skQrToken}");
        $pubSk->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'VALID')
            ->assertJsonPath('data.qr_type', 'decision_sk')
            ->assertJsonPath('data.entity.title', 'SK Penetapan Penerima Hibah Combine Harvester')
            ->assertJsonPath('data.signature.is_verified', true);

        // Assert privacy protection: no storage paths or private personal info
        $this->assertArrayNotHasKey('storage_path', $pubSk->json('data.entity') ?? []);
        $this->assertArrayNotHasKey('nik', $pubSk->json('data.entity') ?? []);

        // 6.2 Verify Realization Item QR (Physical Asset)
        $pubItem = $this->getJson("/api/v1/public/verify/{$itemQrToken}");
        $pubItem->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'VALID')
            ->assertJsonPath('data.qr_type', 'realization_item')
            ->assertJsonPath('data.entity.serial_number', 'KUB-DC35-889900')
            ->assertJsonPath('data.entity.status', 'monitored');

        // 6.3 Verify Receipt QR
        $pubReceipt = $this->getJson("/api/v1/public/verify/{$receiptQrToken}");
        $pubReceipt->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'VALID')
            ->assertJsonPath('data.qr_type', 'receipt')
            ->assertJsonPath('data.entity.recipient_name', 'PT Kubota Indonesia Raya')
            ->assertJsonPath('data.entity.amount', 120000000);

        // 6.4 Verify Handover (BAST) QR
        $pubBast = $this->getJson("/api/v1/public/verify/{$handoverQrToken}");
        $pubBast->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'VALID')
            ->assertJsonPath('data.qr_type', 'handover')
            ->assertJsonPath('data.entity.recipient_name', 'Kaharuddin Umar (Ketua Poktan)');

        // -------------------------------------------------------------
        // Step 7: Revocation & Security Verification
        // -------------------------------------------------------------
        // Cancel receipt and check public verification returns REVOKED
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/receipts/{$receiptId}/cancel", [
            'reason' => 'Audit menemukan penggantian kwitansi distributor',
        ])->assertStatus(200);

        Sanctum::actingAs(new User);
        $revokedReceipt = $this->getJson("/api/v1/public/verify/{$receiptQrToken}");
        $revokedReceipt->assertStatus(200)
            ->assertJsonPath('data.status', 'REVOKED')
            ->assertJsonPath('data.is_authentic', false);

        // Non-existent token returns 404 NOT_FOUND
        $notFound = $this->getJson('/api/v1/public/verify/NON_EXISTENT_TOKEN_1234567890ABCDEF');
        $notFound->assertStatus(404)
            ->assertJsonPath('errors.status', 'NOT_FOUND')
            ->assertJsonPath('errors.is_valid', false);
    }
}
