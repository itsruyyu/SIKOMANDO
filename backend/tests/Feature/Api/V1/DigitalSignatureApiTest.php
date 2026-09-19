<?php

namespace Tests\Feature\Api\V1;

use App\Enums\QrStatus;
use App\Enums\SignatureStatus;
use App\Models\Decision;
use App\Models\DigitalSignature;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\SignatureProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DigitalSignatureApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $signer;

    private Decision $decision;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('private');

        $superAdminRole = Role::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['id' => (string) Str::uuid(), 'name' => 'Super Admin', 'is_system' => true, 'is_active' => true]
        );

        $approverRole = Role::firstOrCreate(
            ['code' => 'APPROVER'],
            ['id' => (string) Str::uuid(), 'name' => 'Approver', 'is_system' => false, 'is_active' => true]
        );

        $this->admin = User::factory()->create(['name' => 'Admin SIKOMANDO']);
        $this->admin->roles()->sync([$superAdminRole->id]);

        $this->signer = User::factory()->create(['name' => 'Dr. H. Kepala Dinas, M.Si']);
        $this->signer->roles()->sync([$approverRole->id]);

        $grantProgram = GrantProgram::factory()->create();
        $organization = Organization::factory()->create();
        $proposal = Proposal::factory()->create([
            'grant_program_id' => $grantProgram->id,
            'organization_id' => $organization->id,
            'title' => 'Pemberdayaan UMKM',
        ]);

        $this->decision = Decision::create([
            'proposal_id' => $proposal->id,
            'decision_number' => 'SK/2026/001',
            'title' => 'Surat Keputusan Penetapan Penerima Hibah',
            'status' => 'published',
            'result' => 'approved',
            'decision_date' => now()->toDateString(),
            'approved_amount' => 50000000,
            'issued_by' => $this->signer->id,
            'issued_at' => now(),
        ]);
    }

    public function test_can_create_and_update_signature_profile(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/signatures/profiles', [
            'user_id' => $this->signer->id,
            'name' => 'Dr. H. Kepala Dinas, M.Si',
            'position' => 'Kepala Dinas Komunikasi dan Informatika',
            'nip' => '197501012000031001',
            'authority_level' => 'head_of_department',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Dr. H. Kepala Dinas, M.Si',
                    'position' => 'Kepala Dinas Komunikasi dan Informatika',
                    'status' => 'active',
                ],
            ]);

        $profileId = $response->json('data.id');

        // Update profile
        $updateResponse = $this->putJson("/api/v1/signatures/profiles/{$profileId}", [
            'position' => 'Kepala Dinas Kominfo & Persandian',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'position' => 'Kepala Dinas Kominfo & Persandian',
                ],
            ]);
    }

    public function test_can_upload_visual_signature_specimen(): void
    {
        Sanctum::actingAs($this->admin);

        $profile = SignatureProfile::create([
            'user_id' => $this->signer->id,
            'name' => $this->signer->name,
            'position' => 'Kepala Dinas',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $file = UploadedFile::fake()->create('signature.png', 10, 'image/png');

        $response = $this->postJson("/api/v1/signatures/profiles/{$profile->id}/visual", [
            'signature_image' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull($profile->fresh()->signature_image_path);
    }

    public function test_full_digital_signing_lifecycle_with_cryptographic_hash_and_qr_generation(): void
    {
        Sanctum::actingAs($this->admin);

        $profile = SignatureProfile::create([
            'user_id' => $this->signer->id,
            'name' => $this->signer->name,
            'position' => 'Kepala Dinas',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Put a fake document to sign in storage
        $docContent = 'Surat Keputusan Resmi SIKOMANDO Hibah 2026';
        $docPath = 'documents/decisions/sk-001.pdf';
        Storage::disk('public')->put($docPath, $docContent);

        // 1. Request signature
        $requestResponse = $this->postJson('/api/v1/signatures/request', [
            'signable_type' => 'Decision',
            'signable_id' => $this->decision->id,
            'signer_id' => $this->signer->id,
            'document_path' => $docPath,
        ]);

        $requestResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => SignatureStatus::PENDING_SIGNATURE->value,
                    'signer_name' => $this->signer->name,
                ],
            ]);

        $signatureId = $requestResponse->json('data.id');

        // 2. Signer views pending list
        Sanctum::actingAs($this->signer);
        $pendingResponse = $this->getJson('/api/v1/signatures/pending');
        $pendingResponse->assertStatus(200)
            ->assertJsonFragment(['id' => $signatureId]);

        // 3. Sign document
        $signResponse = $this->postJson("/api/v1/signatures/{$signatureId}/sign", [
            'passphrase' => 'SecretPassphrase123!',
            'notes' => 'Telah diverifikasi dan disetujui sesuai regulasi.',
        ]);

        $signResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => SignatureStatus::SIGNED->value,
                ],
            ]);

        $signedSig = DigitalSignature::find($signatureId);
        $this->assertEquals(SignatureStatus::SIGNED, $signedSig->status);
        $this->assertNotNull($signedSig->document_hash);
        $this->assertEquals(hash('sha256', $docContent), $signedSig->document_hash);

        // Verify that QR identity was automatically created for this decision
        $this->assertDatabaseHas('qr_identities', [
            'qrable_type' => Decision::class,
            'qrable_id' => $this->decision->id,
            'status' => QrStatus::ACTIVE->value,
        ]);

        // 4. Revocation of signature automatically revokes the QR identity
        Sanctum::actingAs($this->admin);
        $revokeResponse = $this->postJson("/api/v1/signatures/{$signatureId}/revoke", [
            'reason' => 'Terdapat kekeliruan lampiran data penerima.',
        ]);

        $revokeResponse->assertStatus(200);
        $this->assertEquals(SignatureStatus::REVOKED, $signedSig->fresh()->status);

        $this->assertNull($this->decision->qrIdentity()->first());
        $qr = $this->decision->qrIdentities()->first();
        $this->assertEquals(QrStatus::REVOKED, $qr->status);
    }

    public function test_signer_can_reject_signature_request(): void
    {
        $profile = SignatureProfile::create([
            'user_id' => $this->signer->id,
            'name' => $this->signer->name,
            'position' => 'Kepala Dinas',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $signature = DigitalSignature::create([
            'signature_profile_id' => $profile->id,
            'signable_type' => Decision::class,
            'signable_id' => $this->decision->id,
            'signer_id' => $this->signer->id,
            'status' => SignatureStatus::PENDING_SIGNATURE,
            'requested_by' => $this->admin->id,
            'signer_name' => $this->signer->name,
            'signer_position' => 'Kepala Dinas',
            'signature_type' => \App\Enums\SignatureType::INTERNAL,
            'document_path' => 'documents/test.pdf',
        ]);

        Sanctum::actingAs($this->signer);

        $response = $this->postJson("/api/v1/signatures/{$signature->id}/reject", [
            'reason' => 'Format nomor keputusan belum sesuai PERBUP.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => SignatureStatus::REJECTED->value,
                    'rejection_reason' => 'Format nomor keputusan belum sesuai PERBUP.',
                ],
            ]);
    }
}
