<?php

namespace App\Models;

use App\Enums\QrVerificationStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrVerificationLog extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'qr_identity_id',
        'token',
        'verifier_user_id',
        'verification_status',
        'scan_context',
        'ip_address',
        'user_agent',
        'scanned_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'verification_status' => QrVerificationStatus::class,
            'metadata' => 'array',
            'scanned_at' => 'datetime',
        ];
    }

    public function qrIdentity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'qr_identity_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_user_id');
    }
}
