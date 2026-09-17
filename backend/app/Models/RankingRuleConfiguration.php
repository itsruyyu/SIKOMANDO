<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingRuleConfiguration extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'grant_program_id',
        'policy_version_id',
        'rule_code',
        'rule_name',
        'rule_definition',
        'priority',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'rule_definition' => 'array',
        'priority' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class, 'grant_program_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
