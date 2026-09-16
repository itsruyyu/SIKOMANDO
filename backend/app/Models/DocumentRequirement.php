<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DocumentRequirement extends Pivot
{
    use HasUuid;

    protected $table = 'document_requirements';

    protected $fillable = [
        'grant_program_id',
        'document_type_id',
        'requirement_id',
        'scope',
        'is_mandatory',
        'maximum_files',
        'validation_rules',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'maximum_files' => 'integer',
            'validation_rules' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function proposalDocuments(): HasMany
    {
        return $this->hasMany(
            ProposalDocument::class,
            'document_requirement_id'
        );
    }
}
