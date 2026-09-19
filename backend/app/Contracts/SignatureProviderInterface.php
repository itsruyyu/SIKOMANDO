<?php

namespace App\Contracts;

use App\Models\DigitalSignature;
use App\Models\SignatureProfile;
use App\Models\User;

interface SignatureProviderInterface
{
    /**
     * Get unique provider identifier code.
     */
    public function getProviderName(): string;

    /**
     * Compute cryptographically secure SHA-256 hash of raw document content or file stream.
     */
    public function calculateHash(string $documentContent): string;

    /**
     * Execute signing process on the digital signature record.
     */
    public function sign(
        DigitalSignature $signature,
        User $signer,
        SignatureProfile $profile,
        string $documentHash,
        ?string $notes = null
    ): DigitalSignature;

    /**
     * Verify authenticity of a digital signature record against document hash.
     */
    public function verify(DigitalSignature $signature, ?string $currentDocumentHash = null): bool;
}

