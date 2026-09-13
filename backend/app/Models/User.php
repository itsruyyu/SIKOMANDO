<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasUuid;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
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
            ->where('code', $role)
            ->exists();
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
    public function organizations()
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
    public function createdGrantPrograms()
    {
        return $this->hasMany(
            GrantProgram::class,
            'created_by'
        );
    }

    /**
     * Get grant programs updated by the user.
     */
    public function updatedGrantPrograms()
    {
        return $this->hasMany(
            GrantProgram::class,
            'updated_by'
        );
    }
}

?>
