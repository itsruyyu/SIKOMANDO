<?php

namespace App\Models;

use App\Enums\DecisionResult;
use App\Enums\DecisionStatus;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Decision extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'proposal_id',
        'approval_id',
        'issued_by',
        'decision_number',
        'decision_type',
        'status',
        'result',
        'decision_date',
        'approved_amount',
        'title',
        'summary',
        'reason',
        'notes',
        'issued_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => DecisionStatus::class,
            'result' => DecisionResult::class,
            'decision_date' => 'date',
            'approved_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function decider(): BelongsTo
    {
        return $this->issuer();
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DecisionDocument::class);
    }

    public function latestDocument(): HasOne
    {
        return $this->hasOne(DecisionDocument::class)->orderByDesc('created_at');
    }
}
