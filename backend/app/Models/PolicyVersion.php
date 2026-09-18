<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyVersion extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'policy_configuration_id',
        'version_number',
        'configuration_data',
        'status',
        'effective_from',
        'effective_until',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'configuration_data' => 'array',
        'effective_from' => 'immutable_datetime',
        'effective_until' => 'immutable_datetime',
        'approved_at' => 'immutable_datetime',
    ];

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(
            PolicyConfiguration::class,
            'policy_configuration_id'
        );
    }

    public function policyConfiguration(): BelongsTo
    {
        return $this->configuration();
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
