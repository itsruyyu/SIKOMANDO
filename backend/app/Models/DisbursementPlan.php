<?php

namespace App\Models;

use App\Enums\DisbursementPlanStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisbursementPlan extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'proposal_id',
        'created_by',
        'plan_number',
        'total_stages',
        'planned_amount',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisbursementPlanStatus::class,
            'total_stages' => 'integer',
            'planned_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class)->orderBy('stage_number');
    }
}
