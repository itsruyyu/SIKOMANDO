<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    use HasUuid;

    protected $fillable = [
        'code',
        'name',
        'description',
        'scope',
        'requirement_type',
        'is_mandatory',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }

    public function verificationItems(): HasMany
    {
        return $this->hasMany(VerificationItem::class);
    }
}