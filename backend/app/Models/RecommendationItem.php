<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'recommendation_id',
        'item_code',
        'item_name',
        'source_type',
        'source_id',
        'result',
        'description',
        'notes',
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'recommendation_id');
    }
}
