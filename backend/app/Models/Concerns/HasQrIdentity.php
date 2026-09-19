<?php

namespace App\Models\Concerns;

use App\Enums\QrStatus;
use App\Models\QrIdentity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasQrIdentity
{
    /**
     * Get the active QR identity for this model.
     */
    public function qrIdentity(): MorphOne
    {
        return $this->morphOne(QrIdentity::class, 'qrable')
            ->where('status', QrStatus::ACTIVE->value);
    }

    /**
     * Get all QR identities (including superseded and revoked) for this model.
     */
    public function qrIdentities(): MorphMany
    {
        return $this->morphMany(QrIdentity::class, 'qrable')
            ->latest('created_at');
    }

    /**
     * Check if model has an active valid QR identity.
     */
    public function hasActiveQr(): bool
    {
        return $this->qrIdentity()->exists();
    }
}

