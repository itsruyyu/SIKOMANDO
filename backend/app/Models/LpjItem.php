<?php

namespace App\Models;

use App\Enums\LpjItemStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpjItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'lpj_submission_id',
        'proposal_budget_item_id',
        'category',
        'budget_item',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'planned_amount',
        'realized_amount',
        'variance',
        'evidence_document_id',
        'evidence_path',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LpjItemStatus::class,
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'planned_amount' => 'decimal:2',
            'realized_amount' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LpjSubmission::class, 'lpj_submission_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(ProposalBudgetItem::class, 'proposal_budget_item_id');
    }

    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(LpjDocument::class, 'evidence_document_id');
    }
}
