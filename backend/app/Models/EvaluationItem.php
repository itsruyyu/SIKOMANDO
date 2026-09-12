<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'evaluation_id',
        'evaluation_criteria_id',
        'weight',
        'score',
        'weighted_score',
        'minimum_score',
        'maximum_score',
        'result',
        'notes',
        'scored_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'score' => 'decimal:4',
            'weighted_score' => 'decimal:4',
            'minimum_score' => 'decimal:4',
            'maximum_score' => 'decimal:4',
            'scored_at' => 'immutable_datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(
            EvaluationCriteria::class,
            'evaluation_criteria_id'
        );
    }
}