<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasUuid;

    protected $fillable = [
        'code',
        'name',
        'scope',
        'allowed_mime_types',
        'max_size_kb',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allowed_mime_types' => 'array',
            'max_size_kb' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function proposalDocuments(): HasMany
    {
        return $this->hasMany(ProposalDocument::class);
    }

    public function organizationDocuments(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }
}
