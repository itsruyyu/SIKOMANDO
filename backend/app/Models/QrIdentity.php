<?php

namespace App\Models;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QrIdentity extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'token',
        'qr_type',
        'qrable_type',
        'qrable_id',
        'qr_code_path',
        'verification_url',
        'status',
        'superseded_by_id',
        'scan_count',
        'last_scanned_at',
        'expires_at',
        'created_by',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'qr_type' => QrType::class,
            'status' => QrStatus::class,
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_scanned_at' => 'datetime',
            'scan_count' => 'integer',
        ];
    }

    public function qrable(): MorphTo
    {
        return $this->morphTo();
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'superseded_by_id');
    }

    public function getTypeAttribute(): ?QrType
    {
        return $this->qr_type;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(QrVerificationLog::class, 'qr_identity_id')->latest('scanned_at');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === QrStatus::REVOKED;
    }

    public function isSuperseded(): bool
    {
        return $this->status === QrStatus::SUPERSEDED;
    }

    public function isActive(): bool
    {
        return $this->status === QrStatus::ACTIVE && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', QrStatus::ACTIVE->value)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
