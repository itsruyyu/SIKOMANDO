<?php

namespace App\Models;

use App\Enums\EvaluationResult;
use App\Enums\EvaluationStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'evaluator_id',
        'evaluation_number',
        'status',
        'total_score',
        'final_score',
        'result',
        'summary',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'result' => EvaluationResult::class,
            'total_score' => 'decimal:4',
            'final_score' => 'decimal:4',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EvaluationItem::class);
    }
}
