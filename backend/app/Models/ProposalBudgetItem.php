<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalBudgetItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'category',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}