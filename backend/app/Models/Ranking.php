<?php

namespace App\Models;

use App\Enums\RankingStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ranking extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'grant_program_id',
        'ranking_rule_configuration_id',
        'policy_version_id',
        'ranking_number',
        'status',
        'weights_snapshot',
        'cutoff_score',
        'total_proposals',
        'recommended_count',
        'not_recommended_count',
        'summary',
        'notes',
        'generated_by',
        'reviewed_by',
        'finalized_by',
        'generated_at',
        'reviewed_at',
        'finalized_at',
    ];

    protected $casts = [
        'status' => RankingStatus::class,
        'weights_snapshot' => 'array',
        'cutoff_score' => 'float',
        'total_proposals' => 'integer',
        'recommended_count' => 'integer',
        'not_recommended_count' => 'integer',
        'generated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class, 'grant_program_id');
    }

    public function ruleConfiguration(): BelongsTo
    {
        return $this->belongsTo(RankingRuleConfiguration::class, 'ranking_rule_configuration_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RankingItem::class, 'ranking_id')->orderBy('rank');
    }
}
