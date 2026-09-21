<?php

namespace App\Services;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Enums\QrVerificationStatus;
use App\Enums\SignatureStatus;
use App\Models\DigitalSignature;
use App\Models\QrIdentity;
use App\Models\QrVerificationLog;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Generate or fetch an active QR Identity for any Eloquent model.
     */
    public function generateFor(
        Model $entity,
        QrType $type,
        ?User $actor = null,
        ?DateTimeInterface $expiresAt = null,
        array $metadata = []
    ): QrIdentity {
        return DB::transaction(function () use ($entity, $type, $actor, $expiresAt, $metadata) {
            // Check if active QR already exists for this entity
            /** @var QrIdentity|null $existing */
            $existing = QrIdentity::where('qrable_type', get_class($entity))
                ->where('qrable_id', $entity->getKey())
                ->where('status', QrStatus::ACTIVE)
                ->first();

            if ($existing) {
                // If metadata was provided, merge it
                if (! empty($metadata)) {
                    $existing->update([
                        'metadata' => array_merge($existing->metadata ?? [], $metadata),
                    ]);
                }

                return $existing;
            }

            // Generate high-entropy 48-character token
            $token = Str::random(48);

            // Verification URL
            $verificationUrl = url("/api/v1/public/verify/{$token}");

            // Generate SVG QR Code file
            $svgContent = $this->generateSvgQrContent($verificationUrl, $token);
            $fileName = "qrcodes/{$token}.svg";
            Storage::disk('public')->put($fileName, $svgContent);

            $qrIdentity = QrIdentity::create([
                'qrable_type' => get_class($entity),
                'qrable_id' => $entity->getKey(),
                'qr_type' => $type,
                'token' => $token,
                'qr_code_path' => $fileName,
                'verification_url' => $verificationUrl,
                'status' => QrStatus::ACTIVE,
                'metadata' => $metadata,
                'expires_at' => $expiresAt,
                'created_by' => $actor?->id,
            ]);

            $this->auditLogService->record(
                action: 'qr_identity.generated',
                module: 'qr',
                entityType: QrIdentity::class,
                entityId: $qrIdentity->id,
                newValues: [
                    'qr_type' => $type->value,
                    'token' => $token,
                    'qrable_type' => get_class($entity),
                    'qrable_id' => $entity->getKey(),
                ],
                metadata: ['actor_id' => $actor?->id]
            );

            return $qrIdentity;
        });
    }

    /**
     * Public verification method with minimal sanitized disclosure.
     */
    public function verifyToken(
        string $token,
        ?User $actor = null,
        ?string $ip = null,
        ?string $userAgent = null,
        string $context = 'public_web'
    ): array {
        /** @var QrIdentity|null $qr */
        $qr = QrIdentity::where('token', $token)->first();

        if (! $qr) {
            $this->logVerification(
                qr: null,
                token: $token,
                status: QrVerificationStatus::NOT_FOUND,
                ip: $ip,
                userAgent: $userAgent,
                actor: $actor,
                context: $context,
                metadata: ['reason' => 'Token not found']
            );

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::NOT_FOUND->value,
                'message' => 'QR Code atau Token tidak ditemukan dalam sistem.',
                'token' => $token,
                'verified_at' => now()->toIso8601String(),
                'entity' => null,
                'signature' => null,
            ];
        }

        // Increment scan stats
        $qr->increment('scan_count');
        $qr->update(['last_scanned_at' => now()]);

        // 1. Check if superseded
        if ($qr->status === QrStatus::SUPERSEDED) {
            $this->logVerification($qr, $token, QrVerificationStatus::SUPERSEDED, $ip, $userAgent, $actor, $context);

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::SUPERSEDED->value,
                'message' => 'QR Code ini telah digantikan dengan versi terbaru.',
                'token' => $token,
                'qr_type' => $qr->qr_type->value,
                'type' => $qr->qr_type->value,
                'verified_at' => now()->toIso8601String(),
                'superseded_by_token' => $qr->supersededBy?->token,
                'entity' => $this->sanitizeEntityForPublic($qr),
                'signature' => $this->sanitizeSignatureForPublic($qr),
            ];
        }

        // 2. Check if revoked
        if ($qr->status === QrStatus::REVOKED) {
            $this->logVerification($qr, $token, QrVerificationStatus::REVOKED, $ip, $userAgent, $actor, $context, [
                'revocation_reason' => $qr->revocation_reason,
            ]);

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::REVOKED->value,
                'message' => 'QR Code ini telah dicabut dan tidak lagi berlaku.',
                'token' => $token,
                'qr_type' => $qr->qr_type->value,
                'type' => $qr->qr_type->value,
                'revoked_at' => $qr->revoked_at?->toIso8601String(),
                'revocation_reason' => $qr->revocation_reason,
                'verified_at' => now()->toIso8601String(),
                'entity' => $this->sanitizeEntityForPublic($qr),
                'signature' => $this->sanitizeSignatureForPublic($qr),
            ];
        }

        // 3. Check if expired
        if ($qr->status === QrStatus::EXPIRED || ($qr->expires_at && $qr->expires_at->isPast())) {
            if ($qr->status !== QrStatus::EXPIRED) {
                $qr->update(['status' => QrStatus::EXPIRED]);
            }

            $this->logVerification($qr, $token, QrVerificationStatus::EXPIRED, $ip, $userAgent, $actor, $context);

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::EXPIRED->value,
                'message' => 'Masa berlaku QR Code ini telah berakhir.',
                'token' => $token,
                'qr_type' => $qr->qr_type->value,
                'type' => $qr->qr_type->value,
                'expires_at' => $qr->expires_at?->toIso8601String(),
                'verified_at' => now()->toIso8601String(),
                'entity' => $this->sanitizeEntityForPublic($qr),
                'signature' => $this->sanitizeSignatureForPublic($qr),
            ];
        }

        // 4. Validate underlying entity integrity & signature
        $entity = $qr->qrable;
        if (! $entity) {
            $this->logVerification($qr, $token, QrVerificationStatus::INVALID, $ip, $userAgent, $actor, $context, [
                'reason' => 'Underlying entity missing',
            ]);

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::INVALID->value,
                'message' => 'Objek atau dokumen terkait QR ini tidak ditemukan atau rusak.',
                'token' => $token,
                'qr_type' => $qr->qr_type->value,
                'type' => $qr->qr_type->value,
                'verified_at' => now()->toIso8601String(),
                'entity' => null,
                'signature' => null,
            ];
        }

        // Check if digital signature exists and its validity
        $signatureInfo = $this->sanitizeSignatureForPublic($qr);
        if ($signatureInfo && isset($signatureInfo['status']) && $signatureInfo['status'] === SignatureStatus::REVOKED->value) {
            $this->logVerification($qr, $token, QrVerificationStatus::REVOKED, $ip, $userAgent, $actor, $context, [
                'reason' => 'Signature revoked',
            ]);

            return [
                'is_valid' => false,
                'is_authentic' => false,
                'status' => QrVerificationStatus::REVOKED->value,
                'message' => 'Tanda tangan digital pada dokumen ini telah dicabut.',
                'token' => $token,
                'qr_type' => $qr->qr_type->value,
                'type' => $qr->qr_type->value,
                'verified_at' => now()->toIso8601String(),
                'entity' => $this->sanitizeEntityForPublic($qr),
                'signature' => $signatureInfo,
            ];
        }

        // All good - QR is authentic and VALID
        $this->logVerification($qr, $token, QrVerificationStatus::VALID, $ip, $userAgent, $actor, $context);

        return [
            'is_valid' => true,
            'is_authentic' => true,
            'status' => QrVerificationStatus::VALID->value,
            'message' => 'QR Code valid dan terverifikasi secara resmi pada SIKOMANDO.',
            'token' => $token,
            'qr_type' => $qr->qr_type->value,
            'type' => $qr->qr_type->value,
            'verified_at' => now()->toIso8601String(),
            'scan_count' => $qr->scan_count,
            'entity' => $this->sanitizeEntityForPublic($qr),
            'signature' => $signatureInfo,
        ];
    }

    /**
     * Authorized internal operational resolution of a QR token.
     */
    public function resolveToken(string $token, User $actor, ?string $ip = null, ?string $userAgent = null): array
    {
        $verification = $this->verifyToken(
            token: $token,
            actor: $actor,
            ip: $ip,
            userAgent: $userAgent,
            context: 'internal_resolution'
        );

        if (! $verification['is_valid'] && $verification['status'] === QrVerificationStatus::NOT_FOUND->value) {
            throw ValidationException::withMessages([
                'token' => 'QR Code tidak ditemukan dalam database.',
            ]);
        }

        /** @var QrIdentity $qr */
        $qr = QrIdentity::where('token', $token)->with(['qrable', 'supersededBy', 'creator'])->firstOrFail();

        return [
            'verification' => $verification,
            'qr_identity' => [
                'id' => $qr->id,
                'qr_type' => $qr->qr_type->value,
                'status' => $qr->status->value,
                'token' => $qr->token,
                'verification_url' => $qr->verification_url,
                'qr_code_path' => $qr->qr_code_path,
                'scan_count' => $qr->scan_count,
                'last_scanned_at' => $qr->last_scanned_at?->toIso8601String(),
                'created_at' => $qr->created_at->toIso8601String(),
                'metadata' => $qr->metadata,
            ],
            'entity_details' => $this->resolveFullEntityForActor($qr, $actor),
        ];
    }

    /**
     * Revoke an active QR identity.
     */
    public function revoke(QrIdentity $qr, User $actor, string $reason): QrIdentity
    {
        if ($qr->status === QrStatus::REVOKED) {
            throw ValidationException::withMessages([
                'status' => 'QR Code sudah dalam status dicabut.',
            ]);
        }

        return DB::transaction(function () use ($qr, $actor, $reason) {
            $qr->update([
                'status' => QrStatus::REVOKED,
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);

            $this->auditLogService->record(
                action: 'qr_identity.revoked',
                module: 'qr',
                entityType: QrIdentity::class,
                entityId: $qr->id,
                newValues: [
                    'status' => QrStatus::REVOKED->value,
                    'reason' => $reason,
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $qr->fresh();
        });
    }

    /**
     * Supersede and regenerate QR Identity for an entity.
     */
    public function regenerate(QrIdentity $oldQr, User $actor, string $reason): QrIdentity
    {
        return DB::transaction(function () use ($oldQr, $actor, $reason) {
            $entity = $oldQr->qrable;
            if (! $entity) {
                throw ValidationException::withMessages([
                    'entity' => 'Entitas yang terkait dengan QR ini tidak ditemukan.',
                ]);
            }

            // Mark old QR as superseded first so generateFor creates a fresh QR
            $oldQr->update([
                'status' => QrStatus::SUPERSEDED,
                'revocation_reason' => 'Digantikan dengan QR baru: '.$reason,
            ]);

            // Create new QR
            $newQr = $this->generateFor(
                entity: $entity,
                type: $oldQr->qr_type,
                actor: $actor,
                expiresAt: $oldQr->expires_at,
                metadata: array_merge($oldQr->metadata ?? [], [
                    'regenerated_from_id' => $oldQr->id,
                    'regeneration_reason' => $reason,
                ])
            );

            // Link old QR to new QR
            $oldQr->update([
                'superseded_by_id' => $newQr->id,
            ]);

            $this->auditLogService->record(
                action: 'qr_identity.regenerated',
                module: 'qr',
                entityType: QrIdentity::class,
                entityId: $oldQr->id,
                newValues: [
                    'status' => QrStatus::SUPERSEDED->value,
                    'new_qr_id' => $newQr->id,
                    'reason' => $reason,
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $newQr;
        });
    }

    /**
     * Log verification attempts.
     */
    protected function logVerification(
        ?QrIdentity $qr,
        string $token,
        QrVerificationStatus $status,
        ?string $ip,
        ?string $userAgent,
        ?User $actor,
        string $context,
        array $metadata = []
    ): QrVerificationLog {
        return QrVerificationLog::create([
            'qr_identity_id' => $qr?->id,
            'token' => $token,
            'verification_status' => $status,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500) : null,
            'verifier_user_id' => $actor?->id,
            'scan_context' => $context,
            'metadata' => $metadata,
            'scanned_at' => now(),
        ]);
    }

    /**
     * Sanitize entity details for public verification to prevent leaking sensitive info.
     */
    protected function sanitizeEntityForPublic(QrIdentity $qr): ?array
    {
        $entity = $qr->qrable;
        if (! $entity) {
            return null;
        }

        $type = $qr->qr_type;

        return match ($type) {
            QrType::DECISION_SK => [
                'type' => 'Keputusan Penetapan Hibah (SK)',
                'sk_number' => $entity->sk_number ?? $entity->decision_number ?? $entity->number ?? 'N/A',
                'title' => $entity->title ?? 'Surat Keputusan Penerima Hibah',
                'issued_date' => $entity->sk_date?->format('d F Y') ?? $entity->decision_date?->format('d F Y') ?? $entity->created_at?->format('d F Y'),
                'program_name' => $entity->grantProgram?->name ?? $entity->proposal?->grantProgram?->name ?? 'Program Hibah Daerah',
                'beneficiary_name' => $entity->proposal?->organization?->name ?? $entity->recipient_name ?? null,
            ],
            QrType::RECEIPT => [
                'type' => 'Kuitansi Resmi Realisasi Hibah',
                'receipt_number' => $entity->receipt_number,
                'title' => $entity->title ?? 'Kuitansi Pembayaran Realisasi',
                'amount' => (float) $entity->amount,
                'receipt_date' => $entity->receipt_date?->format('d F Y'),
                'status' => $entity->status?->value ?? (string) $entity->status,
                'recipient_name' => $entity->recipient_name,
                'payer_name' => $entity->payer_name,
                'organization_name' => $entity->proposal?->organization?->name ?? null,
            ],
            QrType::HANDOVER => [
                'type' => 'Berita Acara Serah Terima (BAST)',
                'handover_number' => $entity->handover_number,
                'title' => $entity->title ?? 'Berita Acara Serah Terima (BAST)',
                'handover_date' => $entity->handover_date?->format('d F Y'),
                'status' => $entity->status?->value ?? (string) $entity->status,
                'giver_name' => $entity->giver_name,
                'recipient_name' => $entity->recipient_name,
                'handover_to' => $entity->recipient_name,
                'items_count' => $entity->items()->count(),
            ],
            QrType::REALIZATION_ITEM => [
                'type' => 'Barang / Aset Hasil Realisasi Hibah',
                'item_code' => $entity->item_code,
                'item_name' => $entity->item_name,
                'serial_number' => $entity->serial_number,
                'specification' => $entity->specification,
                'quantity' => $entity->quantity,
                'unit' => $entity->unit,
                'status' => $entity->status?->value ?? (string) $entity->status,
                'condition' => $entity->condition?->value ?? (string) $entity->condition,
                'organization_name' => $entity->package?->proposal?->organization?->name ?? null,
            ],
            QrType::REALIZATION_PACKAGE => [
                'type' => 'Paket Realisasi Anggaran Hibah',
                'package_number' => $entity->package_number,
                'package_name' => $entity->package_name,
                'total_amount' => (float) $entity->total_amount,
                'status' => $entity->status?->value ?? (string) $entity->status,
                'organization_name' => $entity->proposal?->organization?->name ?? null,
            ],
            QrType::PROPOSAL => [
                'type' => 'Proposal Hibah',
                'proposal_number' => $entity->proposal_number,
                'title' => $entity->title,
                'organization_name' => $entity->organization?->name,
                'program_name' => $entity->grantProgram?->name,
                'status' => $entity->status?->value ?? (string) $entity->status,
            ],
            QrType::LPJ => [
                'type' => 'Laporan Pertanggungjawaban (LPJ)',
                'lpj_number' => $entity->submission_number ?? $entity->number ?? 'N/A',
                'status' => $entity->status?->value ?? (string) $entity->status,
                'organization_name' => $entity->proposal?->organization?->name,
            ],
            default => [
                'type' => 'Dokumen Terverifikasi SIKOMANDO',
                'reference' => $entity->title ?? $entity->name ?? 'Dokumen Resmi',
                'created_at' => $entity->created_at?->format('d F Y'),
            ],
        };
    }

    /**
     * Sanitize signature info for public view.
     */
    protected function sanitizeSignatureForPublic(QrIdentity $qr): ?array
    {
        $entity = $qr->qrable;
        if (! $entity) {
            return null;
        }

        // Check if entity has digital signatures
        /** @var DigitalSignature|null $sig */
        $sig = null;
        if (method_exists($entity, 'digitalSignatures')) {
            $sig = $entity->digitalSignatures()->latest()->first();
        } elseif (isset($qr->metadata['signature_id'])) {
            $sig = DigitalSignature::find($qr->metadata['signature_id']);
        }

        if (! $sig) {
            return null;
        }

        return [
            'signer_name' => $sig->signer_name,
            'signer_position' => $sig->signer_position,
            'signed_at' => $sig->signed_at?->format('d F Y H:i:s T'),
            'status' => $sig->status->value,
            'document_hash' => $sig->document_hash ? substr($sig->document_hash, 0, 12).'...'.substr($sig->document_hash, -8) : null,
            'signature_type' => $sig->signature_type->value,
            'is_verified' => $sig->status === SignatureStatus::SIGNED,
        ];
    }

    /**
     * Resolve full entity data for authorized internal operational personnel.
     */
    protected function resolveFullEntityForActor(QrIdentity $qr, User $actor): array
    {
        $entity = $qr->qrable;
        if (! $entity) {
            return [];
        }

        $isInternal = $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'VERIFIKATOR', 'SURVEYOR']);

        return match ($qr->qr_type) {
            QrType::REALIZATION_ITEM => [
                'item' => $entity->load(['package.proposal.organization', 'budgetItem', 'receipt', 'histories']),
                'survey_findings' => method_exists($entity, 'surveyFindings') ? $entity->surveyFindings : [],
                'monitoring_items' => method_exists($entity, 'monitoringItems') ? $entity->monitoringItems()->with('monitoringRecord')->get() : [],
            ],
            QrType::REALIZATION_ITEM => array_filter([
                'item' => $entity->load(['package.proposal.organization', 'budgetItem', 'receipt']),
                'histories' => $isInternal ? $entity->histories : [],
                'survey_findings' => ($isInternal && method_exists($entity, 'surveyFindings')) ? $entity->surveyFindings : [],
                'monitoring_items' => ($isInternal && method_exists($entity, 'monitoringItems')) ? $entity->monitoringItems()->with('monitoringRecord')->get() : [],
            ]),
            QrType::REALIZATION_PACKAGE => [
                'package' => $entity->load(['proposal.organization', 'items', 'receipts', 'handovers']),
            ],
            QrType::HANDOVER => [
                'handover' => $entity->load(['package', 'proposal.organization', 'items', 'digitalSignatures']),
            ],
            QrType::RECEIPT => [
                'receipt' => $entity->load(['package', 'proposal.organization', 'realizationItem']),
            ],
            default => [
                'entity' => $entity->toArray(),
                'entity' => [
                    'id' => $entity->id,
                    'title' => $entity->title ?? $entity->name ?? 'Dokumen Resmi',
                    'status' => $entity->status?->value ?? (string) ($entity->status ?? ''),
                    'created_at' => $entity->created_at?->toISOString(),
                ],
            ],
        };
    }

    /**
     * Generate an SVG QR graphic with distinct finder patterns and data points.
     */
    protected function generateSvgQrContent(string $url, string $token): string
    {
        try {
            return (string) QrCode::format('svg')
                ->size(240)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($url);
        } catch (\Throwable $e) {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240, 1),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);

            return $writer->writeString($url);
        }
    }
}
