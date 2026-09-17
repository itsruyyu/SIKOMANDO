<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationWeightConfiguration extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'grant_program_id',
        'policy_version_id',
        'evaluation_criteria_id',
        'weight',
        'minimum_score',
        'maximum_score',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'minimum_score' => 'decimal:2',
        'maximum_score' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(
            GrantProgram::class,
            'grant_program_id'
        );
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(
            PolicyVersion::class,
            'policy_version_id'
        );
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(
            EvaluationCriteria::class,
            'evaluation_criteria_id'
        );
    }
}
