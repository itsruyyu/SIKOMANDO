<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogService
{
    public function record(
        string $action,
        string $module,
        ?string $entityType = null,
        ?string $entityId = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?string $requestId = null,
        ?string $actorId = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => Auth::id(),
            'actor_id' => $actorId ?: Auth::id(),
            'action' => $action,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'request_id' => $requestId ?: (string) Str::uuid(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => $this->sanitize($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function sanitize(array $values): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'bank_account_number',
            'account_number',
        ];

        foreach ($sensitiveKeys as $key) {
            unset($values[$key]);
        }

        return $values;
    }
}
