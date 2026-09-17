<?php

namespace App\Models;

use App\Enums\LpjStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LpjSubmission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'proposal_id',
        'organization_id',
        'grant_program_id',
        'decision_id',
        'disbursement_id',
        'stage_number',
        'submitted_by',
        'verified_by',
        'approved_by',
        'closed_by',
        'lpj_number',
        'status',
        'total_received',
        'total_spent',
        'remaining_balance',
        'summary',
        'notes',
        'verification_notes',
        'revision_reason',
        'rejection_reason',
        'submitted_at',
        'verified_at',
        'approved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LpjStatus::class,
            'stage_number' => 'integer',
            'total_received' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LpjItem::class, 'lpj_submission_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LpjDocument::class, 'lpj_submission_id');
    }
}
