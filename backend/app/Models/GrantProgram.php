<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantProgram extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'code',
        'name',
        'description',
        'fiscal_year',
        'status',
        'registration_start_at',
        'registration_end_at',
        'minimum_amount',
        'maximum_amount',
        'total_budget',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'registration_start_at' => 'immutable_datetime',
            'registration_end_at' => 'immutable_datetime',
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'total_budget' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }

    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(
            Requirement::class,
            'document_requirements',
            'grant_program_id',
            'requirement_id'
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

    public function evaluationWeightConfigurations(): HasMany
    {
        return $this->hasMany(EvaluationWeightConfiguration::class);
    }
}
