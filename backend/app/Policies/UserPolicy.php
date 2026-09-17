<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR']);
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO', 'AUDITOR']);
    }

    /**
     * Determine whether the user can create users.
     * Only Super Admin can create users.
     */
    public function create(User $actor): bool
    {
        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can update the target user.
     */
    public function update(User $actor, User $target): bool
    {
        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can delete the target user.
     */
    public function delete(User $actor, User $target): bool
    {
        // Super Admin accounts can NEVER be deleted
        if ($target->hasRole('SUPER_ADMIN')) {
            return false;
        }

        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $actor, User $target): bool
    {
        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can assign/remove roles.
     */
    public function manageRoles(User $actor): bool
    {
        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can reset passwords.
     */
    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can list users by role (for assignments).
     */
    public function listByRole(User $actor): bool
    {
        return $actor->hasAnyRole(['SUPER_ADMIN', 'ADMIN_SIKOMANDO']);
    }
}
