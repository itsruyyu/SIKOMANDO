<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationCriteria extends Model
{
    use HasFactory;
    use HasUuids;

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

    protected $casts = [
        'default_weight' => 'decimal:4',
        'minimum_score' => 'decimal:2',
        'maximum_score' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}