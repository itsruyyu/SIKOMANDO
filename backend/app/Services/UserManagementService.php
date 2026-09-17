<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Create a new user account.
     *
     * @throws ValidationException
     */
    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Assign roles if provided
            if (! empty($data['roles'])) {
                $this->syncRolesWithGuard($user, $data['roles']);
            }

            $this->auditLogService->record(
                action: 'user.created',
                module: 'user_management',
                entityType: User::class,
                entityId: $user->id,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $data['roles'] ?? [],
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $user->load('roles');
        });
    }

    /**
     * Update user account data.
     *
     * @throws ValidationException
     */
    public function updateUser(User $user, array $data, User $actor): User
    {
        $this->guardSuperAdminIntegrity($user, $actor, 'mengubah');

        return DB::transaction(function () use ($user, $data, $actor) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            $user->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], fn ($v) => $v !== null));

            $this->auditLogService->record(
                action: 'user.updated',
                module: 'user_management',
                entityType: User::class,
                entityId: $user->id,
                oldValues: $oldValues,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                metadata: ['actor_id' => $actor->id]
            );

            return $user->fresh('roles');
        });
    }

    /**
     * Toggle user active status.
     *
     * @throws ValidationException
     */
    public function toggleActive(User $user, User $actor): User
    {
        // Super Admin cannot be deactivated
        if ($user->hasRole('SUPER_ADMIN') && $user->is_active) {
            throw ValidationException::withMessages([
                'user' => 'Akun Super Admin tidak dapat dinonaktifkan.',
            ]);
        }

        return DB::transaction(function () use ($user, $actor) {
            $oldActive = $user->is_active;
            $user->update(['is_active' => ! $user->is_active]);

            // Revoke all tokens when deactivating
            if (! $user->is_active) {
                $user->tokens()->delete();
            }

            $this->auditLogService->record(
                action: $user->is_active ? 'user.activated' : 'user.deactivated',
                module: 'user_management',
                entityType: User::class,
                entityId: $user->id,
                oldValues: ['is_active' => $oldActive],
                newValues: ['is_active' => $user->is_active],
                metadata: ['actor_id' => $actor->id]
            );

            return $user->fresh('roles');
        });
    }

    /**
     * Assign a role to a user.
     *
     * @throws ValidationException
     */
    public function assignRole(User $user, string $roleCode, User $actor): User
    {
        $role = Role::where('code', $roleCode)->first();
        if (! $role) {
            throw ValidationException::withMessages([
                'role' => "Role dengan kode '{$roleCode}' tidak ditemukan.",
            ]);
        }

        // Prevent creating a second SUPER_ADMIN
        if ($roleCode === 'SUPER_ADMIN') {
            $existingSuperAdmin = User::whereHas('roles', function ($q) {
                $q->where('code', 'SUPER_ADMIN');
            })->where('id', '!=', $user->id)->exists();

            if ($existingSuperAdmin) {
                throw ValidationException::withMessages([
                    'role' => 'Sistem hanya mengizinkan satu akun Super Admin.',
                ]);
            }
        }

        // Cannot change own role
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'role' => 'Tidak dapat mengubah role akun sendiri.',
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->auditLogService->record(
            action: 'user.role_assigned',
            module: 'user_management',
            entityType: User::class,
            entityId: $user->id,
            newValues: ['role' => $roleCode],
            metadata: ['actor_id' => $actor->id]
        );

        return $user->fresh('roles');
    }

    /**
     * Remove a role from a user.
     *
     * @throws ValidationException
     */
    public function removeRole(User $user, string $roleCode, User $actor): User
    {
        // Cannot remove SUPER_ADMIN role
        if ($roleCode === 'SUPER_ADMIN') {
            throw ValidationException::withMessages([
                'role' => 'Role Super Admin tidak dapat dicabut melalui operasi ini.',
            ]);
        }

        // Cannot change own role
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'role' => 'Tidak dapat mengubah role akun sendiri.',
            ]);
        }

        $role = Role::where('code', $roleCode)->first();
        if (! $role) {
            throw ValidationException::withMessages([
                'role' => "Role dengan kode '{$roleCode}' tidak ditemukan.",
            ]);
        }

        $user->roles()->detach($role->id);

        $this->auditLogService->record(
            action: 'user.role_removed',
            module: 'user_management',
            entityType: User::class,
            entityId: $user->id,
            oldValues: ['role' => $roleCode],
            metadata: ['actor_id' => $actor->id]
        );

        return $user->fresh('roles');
    }

    /**
     * Reset user password.
     *
     * @throws ValidationException
     */
    public function resetPassword(User $user, string $newPassword, User $actor): User
    {
        $this->guardSuperAdminIntegrity($user, $actor, 'mereset password');

        return DB::transaction(function () use ($user, $newPassword, $actor) {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // Revoke all existing tokens
            $user->tokens()->delete();

            $this->auditLogService->record(
                action: 'user.password_reset',
                module: 'user_management',
                entityType: User::class,
                entityId: $user->id,
                metadata: [
                    'actor_id' => $actor->id,
                    'note' => 'Password telah direset dan seluruh token dicabut.',
                ]
            );

            return $user->fresh('roles');
        });
    }

    /**
     * Sync roles with guard against duplicate SUPER_ADMIN.
     *
     * @throws ValidationException
     */
    protected function syncRolesWithGuard(User $user, array $roleCodes): void
    {
        if (in_array('SUPER_ADMIN', $roleCodes, true)) {
            $existingSuperAdmin = User::whereHas('roles', function ($q) {
                $q->where('code', 'SUPER_ADMIN');
            })->where('id', '!=', $user->id)->exists();

            if ($existingSuperAdmin) {
                throw ValidationException::withMessages([
                    'roles' => 'Sistem hanya mengizinkan satu akun Super Admin.',
                ]);
            }
        }

        $roleIds = Role::whereIn('code', $roleCodes)->pluck('id')->all();
        $user->roles()->sync($roleIds);
    }

    /**
     * Guard operations that could compromise Super Admin integrity.
     *
     * @throws ValidationException
     */
    protected function guardSuperAdminIntegrity(User $target, User $actor, string $actionVerb): void
    {
        if ($target->hasRole('SUPER_ADMIN') && ! $actor->hasRole('SUPER_ADMIN')) {
            throw ValidationException::withMessages([
                'user' => "Tidak memiliki kewenangan untuk {$actionVerb} akun Super Admin.",
            ]);
        }
    }
}
