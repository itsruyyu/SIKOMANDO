<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class HandoverItem extends Pivot
{
    use HasFactory, HasUuid;

    protected $table = 'handover_items';

    protected $fillable = [
        'handover_id',
        'realization_item_id',
        'notes',
    ];

    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class, 'handover_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RealizationItem::class, 'realization_item_id');
    }
}
