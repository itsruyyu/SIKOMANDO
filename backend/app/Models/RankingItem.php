<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'ranking_id',
        'proposal_id',
        'rank',
        'evaluation_score',
        'survey_score',
        'final_score',
        'status',
        'recommendation_result',
        'recommendation_reason',
        'notes',
        'snapshot_data',
    ];

    protected $casts = [
        'rank' => 'integer',
        'evaluation_score' => 'float',
        'survey_score' => 'float',
        'final_score' => 'float',
        'snapshot_data' => 'array',
    ];

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class, 'ranking_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }
}
