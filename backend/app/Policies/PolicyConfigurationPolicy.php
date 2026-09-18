<?php

namespace App\Policies;

use App\Models\PolicyConfiguration;
use App\Models\User;

class PolicyConfigurationPolicy
{
    /**
     * Determine whether the user can view configurations.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'APPROVER']);
    }

    /**
     * Determine whether the user can view a specific configuration.
     */
    public function view(User $actor, PolicyConfiguration $config): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR', 'APPROVER']);
    }

    /**
     * Determine whether the user can create a new version.
     */
    public function createVersion(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    /**
     * Determine whether the user can approve a policy version.
     */
    public function approveVersion(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }

    /**
     * Determine whether the user can activate a policy version.
     */
    public function activateVersion(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }
}
