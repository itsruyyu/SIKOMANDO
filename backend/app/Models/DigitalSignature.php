<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use App\Enums\SignatureType;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalSignature extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'signable_type',
        'signable_id',
        'document_version_id',
        'signer_id',
        'signature_profile_id',
        'signature_type',
        'status',
        'document_hash',
        'signed_at',
        'rejected_at',
        'revoked_at',
        'notes',
        'rejection_reason',
        'revocation_reason',
        'metadata',
    ];

    protected $appends = [
        'signer_name',
        'signer_position',
    ];

    protected function casts(): array
    {
        return [
            'signature_type' => SignatureType::class,
            'status' => SignatureStatus::class,
            'metadata' => 'array',
            'signed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SignatureProfile::class, 'signature_profile_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignerNameAttribute(): string
    {
        return $this->profile?->name ?? $this->signer?->name ?? 'Penandatangan';
    }

    public function getSignerPositionAttribute(): string
    {
        return $this->profile?->position ?? 'Pejabat Berwenang';
    }

    public function isSigned(): bool
    {
        return $this->status === SignatureStatus::SIGNED;
    }
}
