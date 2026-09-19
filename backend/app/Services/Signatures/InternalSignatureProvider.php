<?php

namespace App\Services\Signatures;

use App\Contracts\SignatureProviderInterface;
use App\Enums\SignatureStatus;
use App\Models\DigitalSignature;
use App\Models\SignatureProfile;
use App\Models\User;

class InternalSignatureProvider implements SignatureProviderInterface
{
    public function getProviderName(): string
    {
        return 'SIKOMANDO_INTERNAL';
    }

    public function calculateHash(string $documentContent): string
    {
        return hash('sha256', $documentContent);
    }

    public function sign(
        DigitalSignature $signature,
        User $signer,
        SignatureProfile $profile,
        string $documentHash,
        ?string $notes = null
    ): DigitalSignature {
        $signature->update([
            'status' => SignatureStatus::SIGNED,
            'signature_profile_id' => $profile->id,
            'document_hash' => $documentHash,
            'signed_at' => now(),
            'notes' => $notes ?: 'Ditandatangani secara digital via sistem internal SIKOMANDO.',
            'metadata' => array_merge($signature->metadata ?? [], [
                'signer_name' => $profile->name,
                'signer_position' => $profile->position,
                'signer_nip' => $profile->nip,
                'provider' => $this->getProviderName(),
                'hash_algorithm' => 'SHA-256',
                'signed_timestamp' => now()->toIso8601String(),
            ]),
        ]);

        return $signature->fresh(['signer', 'profile']);
    }

    public function verify(DigitalSignature $signature, ?string $currentDocumentHash = null): bool
    {
        if ($signature->status !== SignatureStatus::SIGNED) {
            return false;
        }

        if (empty($signature->document_hash)) {
            return false;
        }

        if ($currentDocumentHash !== null && ! hash_equals($signature->document_hash, $currentDocumentHash)) {
            return false;
        }

        return true;
    }
}

