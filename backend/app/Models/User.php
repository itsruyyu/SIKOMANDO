<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuid;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
            'password_changed_at' => 'immutable_datetime',
            'must_change_password' => 'boolean',
            'failed_login_attempts' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        );
    }

    /**
     * Check whether the user has one or more roles.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where(function ($query) use ($role) {
                $query
                    ->where('code', $role)
                    ->orWhere('name', $role);
            })
            ->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether the user has one or more permissions.
     *
     * SUPER_ADMIN automatically has all permissions.
     */
    public function hasPermission(string|array $permissions): bool
    {
        $permissions = is_array($permissions)
            ? $permissions
            : [$permissions];

        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('code', $permissions);
            })
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Organizations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the organizations associated with the user.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'organization_user',
            'user_id',
            'organization_id'
        )->withPivot([
            'id',
            'membership_role',
            'is_primary_contact',
            'is_active',
            'joined_at',
            'left_at',
        ])->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Grant Programs
    |--------------------------------------------------------------------------
    */

    /**
     * Get grant programs created by the user.
     */
    public function createdGrantPrograms(): HasMany
    {
        return $this->hasMany(
            GrantProgram::class,
            'created_by'
        );
    }

    /**
     * Get grant programs updated by the user.
     */
    public function updatedGrantPrograms(): HasMany
    {
        return $this->hasMany(
            GrantProgram::class,
            'updated_by'
        );
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function assignedFieldSurveys(): HasMany
    {
        return $this->hasMany(FieldSurvey::class, 'surveyor_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProposalAssignment::class, 'assigned_user_id');
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(ProposalAssignment::class, 'assigned_user_id')
            ->whereIn('status', ['ASSIGNED', 'IN_PROGRESS']);
    }

    public function signatureProfile(): HasOne
    {
        return $this->hasOne(SignatureProfile::class, 'user_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DigitalSignature::class, 'signer_id');
    }
}
