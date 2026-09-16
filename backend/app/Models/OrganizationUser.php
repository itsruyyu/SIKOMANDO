<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    use HasUuid;

    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'membership_role',
        'is_primary_contact',
        'is_active',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'is_active' => 'boolean',
            'joined_at' => 'immutable_date',
            'left_at' => 'immutable_date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
