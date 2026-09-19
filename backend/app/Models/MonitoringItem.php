<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'monitoring_record_id',
        'realization_item_id',
        'scanned_qr_token',
        'indicator_code',
        'indicator_name',
        'description',
        'target_value',
        'actual_value',
        'unit',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:4',
            'actual_value' => 'decimal:4',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MonitoringRecord::class, 'monitoring_record_id');
    }

    public function realizationItem(): BelongsTo
    {
        return $this->belongsTo(RealizationItem::class, 'realization_item_id');
    }

    public function getScannedTokenAttribute(): ?string
    {
        return $this->scanned_qr_token;
    }

    public function setScannedTokenAttribute(?string $value): void
    {
        $this->attributes['scanned_qr_token'] = $value;
    }
}
