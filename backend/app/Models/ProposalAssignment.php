<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalAssignment extends Model
{
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'assigned_user_id',
        'assignment_type',
        'status',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'revoked_at',
        'revoked_by',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'status' => AssignmentStatus::class,
            'assigned_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    // ── Relations ──

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AssignmentStatus::ASSIGNED,
            AssignmentStatus::IN_PROGRESS,
        ]);
    }

    public function scopeForType(Builder $query, AssignmentType $type): Builder
    {
        return $query->where('assignment_type', $type);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('assigned_user_id', $userId);
    }
}
