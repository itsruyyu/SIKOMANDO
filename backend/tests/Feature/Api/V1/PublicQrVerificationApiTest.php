<?php

namespace Tests\Feature\Api\V1;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\QrVerificationStatus;
use App\Models\GrantProgram;
use App\Models\Organization;
use App\Models\Proposal;
use App\Models\QrIdentity;
use App\Models\QrVerificationLog;
use App\Models\Role;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicQrVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemohon;

    private Proposal $proposal;

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

        $this->pemohon = User::factory()->create(['name' => 'Ketua Pemohon']);
        $this->pemohon->roles()->sync([$pemohonRole->id]);

        $grantProgram = GrantProgram::factory()->create();
        $organization = Organization::factory()->create();

        $this->proposal = Proposal::factory()->create([
            'grant_program_id' => $grantProgram->id,
            'organization_id' => $organization->id,
            'applicant_id' => $this->pemohon->id,
            'title' => 'Pengadaan Alat Bantu Digital',
        ]);
    }

    public function test_public_can_verify_active_qr_code_with_sanitized_data(): void
    {
        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $qr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin);

        $response = $this->getJson("/api/v1/public/verify/{$qr->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => true,
                    'status' => QrVerificationStatus::VALID->value,
                    'token' => $qr->token,
                    'qr_type' => QrType::PROPOSAL->value,
                    'entity' => [
                        'type' => 'Proposal Hibah',
                        'title' => 'Pengadaan Alat Bantu Digital',
                    ],
                ],
            ]);

        // Sensitive fields like internal ids, passwords, or emails must not leak
        $response->assertJsonMissing(['password', 'remember_token', 'applicant_id']);

        // Check verification log was recorded
        $this->assertDatabaseHas('qr_verification_logs', [
            'qr_identity_id' => $qr->id,
            'token' => $qr->token,
            'verification_status' => QrVerificationStatus::VALID->value,
            'scan_context' => 'public_web',
        ]);
    }

    public function test_public_verification_returns_revoked_status_for_revoked_qr(): void
    {
        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $qr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin);
        $qrService->revoke($qr, $this->admin, 'Dokumen dibatalkan oleh dinas terkait.');

        $response = $this->getJson("/api/v1/public/verify/{$qr->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => false,
                    'status' => QrVerificationStatus::REVOKED->value,
                    'revocation_reason' => 'Dokumen dibatalkan oleh dinas terkait.',
                ],
            ]);
    }

    public function test_public_verification_returns_superseded_status_and_new_token(): void
    {
        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $oldQr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin);
        $newQr = $qrService->regenerate($oldQr, $this->admin, 'Pembaruan nomor registrasi.');

        $response = $this->getJson("/api/v1/public/verify/{$oldQr->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => false,
                    'status' => QrVerificationStatus::SUPERSEDED->value,
                    'superseded_by_token' => $newQr->token,
                ],
            ]);
    }

    public function test_public_verification_returns_expired_status_for_past_expiry(): void
    {
        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $qr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin, now()->subDay());

        $response = $this->getJson("/api/v1/public/verify/{$qr->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => false,
                    'status' => QrVerificationStatus::EXPIRED->value,
                ],
            ]);
    }

    public function test_public_verification_returns_404_not_found_for_unknown_token(): void
    {
        $unknownToken = Str::random(48);

        $response = $this->getJson("/api/v1/public/verify/{$unknownToken}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'status' => QrVerificationStatus::NOT_FOUND->value,
                    'is_valid' => false,
                ],
            ]);

        $this->assertDatabaseHas('qr_verification_logs', [
            'token' => $unknownToken,
            'verification_status' => QrVerificationStatus::NOT_FOUND->value,
        ]);
    }

    public function test_authenticated_user_can_resolve_qr_internally(): void
    {
        Sanctum::actingAs($this->admin);

        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $qr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin);

        $response = $this->getJson("/api/v1/qr/resolve/{$qr->token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'qr_identity' => [
                        'id' => $qr->id,
                        'token' => $qr->token,
                    ],
                ],
            ]);
    }

    public function test_authorized_user_can_revoke_and_regenerate_qr(): void
    {
        Sanctum::actingAs($this->admin);

        /** @var QrService $qrService */
        $qrService = app(QrService::class);
        $qr = $qrService->generateFor($this->proposal, QrType::PROPOSAL, $this->admin);

        // Revoke
        $revokeResponse = $this->postJson("/api/v1/qr/{$qr->id}/revoke", [
            'reason' => 'Perubahan format dokumen resmi.',
        ]);

        $revokeResponse->assertStatus(200);
        $this->assertEquals(QrStatus::REVOKED, $qr->fresh()->status);

        // Regenerate
        $regenResponse = $this->postJson("/api/v1/qr/{$qr->id}/regenerate", [
            'reason' => 'Penerbitan ulang dengan QR identitas baru.',
        ]);

        $regenResponse->assertStatus(201);
        $this->assertEquals(QrStatus::SUPERSEDED, $qr->fresh()->status);
        $this->assertNotNull($qr->fresh()->superseded_by_id);
    }
}

