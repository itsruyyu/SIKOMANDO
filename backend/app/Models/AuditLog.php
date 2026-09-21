<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'actor_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'request_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'occurred_at',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit logs are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs are append-only and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
