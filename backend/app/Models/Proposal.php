<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'proposal_number',
        'grant_program_id',
        'organization_id',
        'applicant_id',
        'title',
        'background',
        'objectives',
        'benefits',
        'activities',
        'expected_outputs',
        'requested_amount',
        'approved_amount',
        'status',
        'revision_count',
        'submitted_at',
        'verified_at',
        'approved_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'revision_count' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function grantProgram(): BelongsTo
    {
        return $this->belongsTo(GrantProgram::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProposalDocument::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ProposalBudgetItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ProposalStatusHistory::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function fieldSurveys(): HasMany
    {
        return $this->hasMany(FieldSurvey::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class)->orderByDesc('created_at');
    }

    public function rankingItems(): HasMany
    {
        return $this->hasMany(RankingItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function approval(): HasOne
    {
        return $this->hasOne(Approval::class)->orderByDesc('created_at');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function decision(): HasOne
    {
        return $this->hasOne(Decision::class)->orderByDesc('created_at');
    }

    public function disbursementPlans(): HasMany
    {
        return $this->hasMany(DisbursementPlan::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function lpjSubmissions(): HasMany
    {
        return $this->hasMany(LpjSubmission::class);
    }

    public function latestLpjSubmission(): HasOne
    {
        return $this->hasOne(LpjSubmission::class)->orderByDesc('created_at');
    }
}
