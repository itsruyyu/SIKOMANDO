<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'organization_type',
        'description',
        'email',
        'phone',
        'address',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'legal_status',
        'registration_number',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'organization_user',
            'organization_id',
            'user_id'
        )->withPivot([
            'id',
            'membership_role',
            'is_primary_contact',
            'is_active',
            'joined_at',
            'left_at',
        ])->withTimestamps();
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
