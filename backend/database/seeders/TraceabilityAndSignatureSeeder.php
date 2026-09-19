<?php

namespace Database\Seeders;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\QrVerificationStatus;
use App\Enums\SignatureStatus;
use App\Enums\SignatureType;
use App\Models\Decision;
use App\Models\DecisionDocument;
use App\Models\DigitalSignature;
use App\Models\QrIdentity;
use App\Models\QrVerificationLog;
use App\Models\SignatureProfile;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TraceabilityAndSignatureSeeder extends Seeder
{
    public function run(): void
    {
        $approver = User::where('email', 'approver@sikomando.test')->first();
        $admin = User::where('email', 'admin@sikomando.test')->first();
        $qrService = app(QrService::class);

        // 1. Signature Profile for Approver
        $profile = SignatureProfile::updateOrCreate(
            ['user_id' => $approver->id],
            [
                'name' => 'Mayjen TNI (Purn.) Yulius Selvanus Komaling, S.E',
                'position' => 'Gubernur Sulawesi Utara',
                'nip' => '19690905 199303 1 004',
                'signature_image_path' => 'signatures/profiles/'.$approver->id.'/specimen.png',
                'disk' => 'local',
                'status' => 'active',
                'authority_level' => 'KEPALA_DINAS',
                'effective_start_date' => now()->startOfYear(),
                'effective_end_date' => now()->endOfYear(),
                'created_by' => $admin?->id,
            ]
        );

        // 2. Digital Signatures for Decision Documents
        $decisionDocs = DecisionDocument::all();

        foreach ($decisionDocs as $idx => $dDoc) {
            $isSigned = $idx < ($decisionDocs->count() - 1); // leave 1 pending for testing UI

            $sig = DigitalSignature::updateOrCreate(
                [
                    'signable_type' => DecisionDocument::class,
                    'signable_id' => $dDoc->id,
                ],
                [
                    'signer_id' => $approver->id,
                    'signature_profile_id' => $profile->id,
                    'signature_type' => SignatureType::INTERNAL,
                    'status' => $isSigned ? SignatureStatus::SIGNED : SignatureStatus::PENDING_SIGNATURE,
                    'document_hash' => hash('sha256', $dDoc->id.'decision_hash'),
                    'signed_at' => $isSigned ? now()->subDays(12) : null,
                    'notes' => $isSigned ? 'Ditandatangani secara digital (TTE Sah) menggunakan Sertifikat Elektronik SIKOMANDO.' : 'Menunggu penandatanganan spesimen TTE pejabat.',
                ]
            );

            if ($isSigned) {
                try {
                    $qrService->generateFor($sig, QrType::DOCUMENT, $approver, null, [
                        'doc_title' => $dDoc->document_title,
                        'signer' => $profile->name,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        // 3. QR Verification Logs for active QR identities
        $activeQrs = QrIdentity::where('status', QrStatus::ACTIVE)->limit(10)->get();

        foreach ($activeQrs as $qr) {
            QrVerificationLog::create([
                'qr_identity_id' => $qr->id,
                'token' => $qr->qr_token,
                'verification_status' => QrVerificationStatus::VALID,
                'scan_context' => 'public_portal',
                'scanned_at' => now()->subHours(rand(1, 48)),
                'ip_address' => '180.254.120.'.rand(10, 200),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                'created_at' => now()->subHours(rand(1, 48)),
                'updated_at' => now()->subHours(rand(1, 48)),
            ]);

            QrVerificationLog::create([
                'qr_identity_id' => $qr->id,
                'token' => $qr->qr_token,
                'verification_status' => QrVerificationStatus::VALID,
                'scan_context' => 'mobile_camera',
                'scanned_at' => now()->subMinutes(rand(5, 59)),
                'ip_address' => '36.85.99.'.rand(10, 200),
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148',
                'created_at' => now()->subMinutes(rand(5, 59)),
                'updated_at' => now()->subMinutes(rand(5, 59)),
            ]);
        }

        $this->command?->info('Signature profile, digital signatures, and QR verification logs seeded.');
    }
}
