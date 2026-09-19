<?php

namespace App\Services;

use App\Contracts\SignatureProviderInterface;
use App\Enums\QrType;
use App\Enums\SignatureStatus;
use App\Enums\SignatureType;
use App\Models\DigitalSignature;
use App\Models\SignatureProfile;
use App\Models\User;
use App\Services\Signatures\InternalSignatureProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DigitalSignatureService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService,
        protected QrService $qrService,
        protected ?SignatureProviderInterface $provider = null
    ) {
        $this->provider = $provider ?: new InternalSignatureProvider;
    }

    /**
     * Create signature profile for an authorized officer.
     */
    public function createProfile(User $actor, array $data): SignatureProfile
    {
        return DB::transaction(function () use ($actor, $data) {
            $profile = SignatureProfile::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'position' => $data['position'],
                'nip' => $data['nip'] ?? null,
                'status' => $data['status'] ?? 'active',
                'authority_level' => $data['authority_level'] ?? 'officer',
                'effective_start_date' => $data['effective_start_date'] ?? now()->toDateString(),
                'effective_end_date' => $data['effective_end_date'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->auditLogService->record(
                action: 'signature_profile.created',
                module: 'signature',
                entityType: SignatureProfile::class,
                entityId: $profile->id,
                newValues: $profile->toArray(),
                actorId: $actor->id
            );

            return $profile;
        });
    }

    /**
     * Update signature profile.
     */
    public function updateProfile(SignatureProfile $profile, User $actor, array $data): SignatureProfile
    {
        return DB::transaction(function () use ($profile, $actor, $data) {
            $oldValues = $profile->toArray();

            $profile->update(array_filter([
                'name' => $data['name'] ?? $profile->name,
                'position' => $data['position'] ?? $profile->position,
                'nip' => array_key_exists('nip', $data) ? $data['nip'] : $profile->nip,
                'status' => $data['status'] ?? $profile->status,
                'authority_level' => $data['authority_level'] ?? $profile->authority_level,
                'effective_start_date' => $data['effective_start_date'] ?? $profile->effective_start_date,
                'effective_end_date' => array_key_exists('effective_end_date', $data) ? $data['effective_end_date'] : $profile->effective_end_date,
            ], fn ($v) => $v !== null));

            $this->auditLogService->record(
                action: 'signature_profile.updated',
                module: 'signature',
                entityType: SignatureProfile::class,
                entityId: $profile->id,
                oldValues: $oldValues,
                newValues: $profile->toArray(),
                actorId: $actor->id
            );

            return $profile->fresh();
        });
    }

    /**
     * Upload visual signature image for a profile.
     */
    public function uploadSignatureImage(SignatureProfile $profile, UploadedFile $file, User $actor): SignatureProfile
    {
        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'signature_'.Str::slug($profile->name).'_'.Str::random(10).'.'.$extension;
        $path = $file->storeAs('signatures/images', $filename, 'private');

        $profile->update([
            'signature_image_path' => $path,
            'disk' => 'private',
        ]);

        $this->auditLogService->record(
            action: 'signature_profile.image_uploaded',
            module: 'signature',
            entityType: SignatureProfile::class,
            entityId: $profile->id,
            newValues: ['signature_image_path' => $path],
            actorId: $actor->id
        );

        return $profile;
    }

    /**
     * Request a digital signature on a document or entity.
     */
    public function requestSignature(
        Model $signable,
        User $signer,
        User $requester,
        ?string $documentVersionId = null,
        ?string $documentPath = null,
        ?string $notes = null
    ): DigitalSignature {
        return DB::transaction(function () use ($signable, $signer, $requester, $documentVersionId, $documentPath, $notes) {
            $profile = SignatureProfile::query()
                ->where('user_id', $signer->id)
                ->where('status', 'active')
                ->first();

            $signature = DigitalSignature::create([
                'signable_type' => $signable->getMorphClass(),
                'signable_id' => $signable->id,
                'document_version_id' => $documentVersionId,
                'signer_id' => $signer->id,
                'signature_profile_id' => $profile?->id,
                'signature_type' => SignatureType::INTERNAL,
                'status' => SignatureStatus::PENDING_SIGNATURE,
                'notes' => $notes,
                'metadata' => array_filter([
                    'document_path' => $documentPath,
                    'requested_by' => $requester->id,
                ]),
            ]);

            $this->auditLogService->record(
                action: 'digital_signature.requested',
                module: 'signature',
                entityType: DigitalSignature::class,
                entityId: $signature->id,
                newValues: $signature->toArray(),
                actorId: $requester->id
            );

            $this->notificationService->create(
                recipient: $signer,
                type: 'signature.requested',
                title: 'Permintaan Tanda Tangan Digital',
                message: sprintf('Anda diminta menandatangani dokumen %s.', class_basename($signable)),
                entityType: DigitalSignature::class,
                entityId: $signature->id
            );

            return $signature->load(['signer', 'profile']);
        });
    }

    /**
     * Sign the requested document.
     */
    public function sign(
        DigitalSignature $signature,
        User $actor,
        ?string $documentContent = null,
        ?string $notes = null
    ): DigitalSignature {
        if ($signature->status !== SignatureStatus::PENDING_SIGNATURE) {
            throw ValidationException::withMessages([
                'signature' => 'Permintaan tanda tangan ini sudah tidak dalam status menunggu tanda tangan.',
            ]);
        }

        // Validate that actor is the designated signer
        if ($signature->signer_id !== $actor->id && ! $actor->hasRole('SUPER_ADMIN')) {
            throw ValidationException::withMessages([
                'signer' => 'Anda bukan penandatangan yang berwenang untuk dokumen ini.',
            ]);
        }

        $profile = SignatureProfile::query()
            ->where('user_id', $actor->id)
            ->where('status', 'active')
            ->first();

        if (! $profile) {
            throw ValidationException::withMessages([
                'profile' => 'Penandatangan belum memiliki profil tanda tangan aktif yang terdaftar.',
            ]);
        }

        return DB::transaction(function () use ($signature, $actor, $profile, $documentContent, $notes) {
            $docPath = $signature->metadata['document_path'] ?? null;
            if (! $documentContent && $docPath && Storage::disk('public')->exists($docPath)) {
                $documentContent = Storage::disk('public')->get($docPath);
            }

            // Compute document hash or default fallback
            $contentToHash = $documentContent ?: json_encode([
                'entity_type' => $signature->signable_type,
                'entity_id' => $signature->signable_id,
                'signer' => $profile->name,
                'timestamp' => now()->toIso8601String(),
            ]);

            $hash = $this->provider->calculateHash($contentToHash);

            $signed = $this->provider->sign(
                signature: $signature,
                signer: $actor,
                profile: $profile,
                documentHash: $hash,
                notes: $notes
            );

            // Automatically generate or link active QR for the signed entity
            $signable = $signature->signable;
            if ($signable && method_exists($signable, 'qrIdentity')) {
                $qrType = $this->resolveQrTypeForEntity($signable);
                $this->qrService->generateFor(
                    entity: $signable,
                    type: $qrType,
                    actor: $actor,
                    metadata: [
                        'signature_id' => $signature->id,
                        'signer_name' => $profile->name,
                        'signer_position' => $profile->position,
                        'signed_at' => $signed->signed_at?->toIso8601String(),
                        'document_hash' => $hash,
                    ]
                );
            }

            $this->auditLogService->record(
                action: 'digital_signature.signed',
                module: 'signature',
                entityType: DigitalSignature::class,
                entityId: $signed->id,
                newValues: [
                    'status' => SignatureStatus::SIGNED->value,
                    'document_hash' => $hash,
                    'signer_id' => $actor->id,
                ],
                actorId: $actor->id
            );

            return $signed;
        });
    }

    /**
     * Reject a signature request.
     */
    public function reject(DigitalSignature $signature, User $actor, string $reason): DigitalSignature
    {
        if ($signature->status !== SignatureStatus::PENDING_SIGNATURE) {
            throw ValidationException::withMessages([
                'signature' => 'Permintaan tanda tangan sudah tidak dapat ditolak.',
            ]);
        }

        if ($signature->signer_id !== $actor->id && ! $actor->hasRole('SUPER_ADMIN')) {
            throw ValidationException::withMessages([
                'signer' => 'Anda tidak memiliki wewenang untuk menolak tanda tangan ini.',
            ]);
        }

        return DB::transaction(function () use ($signature, $actor, $reason) {
            $signature->update([
                'status' => SignatureStatus::REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->auditLogService->record(
                action: 'digital_signature.rejected',
                module: 'signature',
                entityType: DigitalSignature::class,
                entityId: $signature->id,
                newValues: [
                    'status' => SignatureStatus::REJECTED->value,
                    'reason' => $reason,
                ],
                actorId: $actor->id
            );

            return $signature->fresh(['signer', 'profile']);
        });
    }

    /**
     * Revoke a signed signature.
     */
    public function revoke(DigitalSignature $signature, User $actor, string $reason): DigitalSignature
    {
        if ($signature->status !== SignatureStatus::SIGNED) {
            throw ValidationException::withMessages([
                'signature' => 'Hanya tanda tangan yang telah berstatus SIGNED yang dapat dicabut.',
            ]);
        }

        return DB::transaction(function () use ($signature, $actor, $reason) {
            $signature->update([
                'status' => SignatureStatus::REVOKED,
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);

            // If signable has active QR, revoke it as well
            $signable = $signature->signable;
            if ($signable && method_exists($signable, 'qrIdentity') && $signable->qrIdentity) {
                $this->qrService->revoke(
                    qr: $signable->qrIdentity,
                    actor: $actor,
                    reason: 'Tanda tangan digital dicabut: '.$reason
                );
            }

            $this->auditLogService->record(
                action: 'digital_signature.revoked',
                module: 'signature',
                entityType: DigitalSignature::class,
                entityId: $signature->id,
                newValues: [
                    'status' => SignatureStatus::REVOKED->value,
                    'reason' => $reason,
                ],
                actorId: $actor->id
            );

            return $signature->fresh(['signer', 'profile']);
        });
    }

    protected function resolveQrTypeForEntity(Model $entity): QrType
    {
        $class = get_class($entity);

        return match (true) {
            str_contains($class, 'Decision') => QrType::DECISION_SK,
            str_contains($class, 'Receipt') => QrType::RECEIPT,
            str_contains($class, 'Handover') => QrType::HANDOVER,
            str_contains($class, 'RealizationItem') => QrType::REALIZATION_ITEM,
            str_contains($class, 'RealizationPackage') => QrType::REALIZATION_PACKAGE,
            str_contains($class, 'LpjSubmission') => QrType::LPJ,
            str_contains($class, 'Proposal') => QrType::PROPOSAL,
            str_contains($class, 'GrantProgram') => QrType::GRANT_PROGRAM,
            default => QrType::DOCUMENT,
        };
    }
}
