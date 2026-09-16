<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function grantPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            GrantProgram::class,
            'document_requirements',
            'requirement_id',
            'grant_program_id'
        )->using(DocumentRequirement::class)
            ->withPivot([
                'id',
                'document_type_id',
                'scope',
                'is_mandatory',
                'maximum_files',
                'validation_rules',
                'sort_order',
                'is_active',
            ])->withTimestamps();
    }

    public function verificationItems(): HasMany
    {
        return $this->hasMany(VerificationItem::class);
    }
}
