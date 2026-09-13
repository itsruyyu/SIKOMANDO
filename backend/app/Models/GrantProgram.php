<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrantProgram extends Model
{
    use HasUuid;
    use HasFactory;

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

    public function evaluationWeightConfigurations(): HasMany
    {
        return $this->hasMany(EvaluationWeightConfiguration::class);
    }
}