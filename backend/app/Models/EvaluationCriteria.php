<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCriteria extends Model
{
    use HasUuid;

    protected $table = 'evaluation_criteria';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'description',
        'criterion_type',
        'default_weight',
        'minimum_score',
        'maximum_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_weight' => 'decimal:4',
            'minimum_score' => 'decimal:2',
            'maximum_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            EvaluationItem::class,
            'evaluation_criteria_id'
        );
    }
}
