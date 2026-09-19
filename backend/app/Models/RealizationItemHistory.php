<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealizationItemHistory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'realization_item_id',
        'action',
        'status',
        'new_status',
        'previous_status',
        'actor_id',
        'recorded_by',
        'notes',
        'latitude',
        'longitude',
        'metadata',
    ];

    protected $appends = [
        'new_status',
        'previous_status',
        'recorded_by',
    ];

    public function getNewStatusAttribute(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    public function setNewStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value;
    }

    public function getPreviousStatusAttribute(): ?string
    {
        return $this->metadata['previous_status'] ?? null;
    }

    public function setPreviousStatusAttribute(?string $value): void
    {
        $meta = $this->metadata ?? [];
        $meta['previous_status'] = $value;
        $this->metadata = $meta;
    }

    public function getRecordedByAttribute(): ?string
    {
        return $this->attributes['actor_id'] ?? null;
    }

    public function setRecordedByAttribute(?string $value): void
    {
        $this->attributes['actor_id'] = $value;
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'metadata' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RealizationItem::class, 'realization_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
