<?php

namespace App\Models;

use App\Enums\DisbursementStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Disbursement extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'disbursement_plan_id',
        'proposal_id',
        'stage_number',
        'disbursement_number',
        'planned_amount',
        'approved_amount',
        'paid_amount',
        'status',
        'planned_date',
        'approved_date',
        'paid_date',
        'notes',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisbursementStatus::class,
            'stage_number' => 'integer',
            'planned_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'planned_date' => 'date',
            'approved_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DisbursementPlan::class, 'disbursement_plan_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DisbursementTransaction::class)->orderBy('transaction_date');
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(DisbursementTransaction::class)->orderByDesc('created_at');
    }
}
