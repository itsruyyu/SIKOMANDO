<?php

namespace App\Models;

use App\Enums\RecommendationResult;
use App\Enums\RecommendationStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommendation extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'recommended_by',
        'recommendation_number',
        'status',
        'result',
        'recommended_amount',
        'summary',
        'reason',
        'notes',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => RecommendationStatus::class,
        'result' => RecommendationResult::class,
        'recommended_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecommendationItem::class, 'recommendation_id');
    }
}
