<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR']);
    }

    /**
     * Determine whether the user can view a specific audit log.
     */
    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR']);
    }

    /**
     * Audit logs are append-only via system services; never creatable directly via API.
     */
    public function create(User $actor): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable; never editable.
     */
    public function update(User $actor, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs are immutable; never deletable.
     */
    public function delete(User $actor, AuditLog $auditLog): bool
    {
        return false;
    }
}
