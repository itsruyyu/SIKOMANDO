<?php

namespace App\Models;

use App\Enums\RealizationItemCondition;
use App\Enums\RealizationItemStatus;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RealizationItem extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'realization_package_id',
        'proposal_budget_item_id',
        'item_code',
        'name',
        'category',
        'serial_number',
        'brand',
        'model',
        'specification',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'purchase_date',
        'location_name',
        'latitude',
        'longitude',
        'condition',
        'status',
        'item_name',
        'total_price',
        'location_address',
        'responsible_person',
        'notes',
        'created_by',
    ];

    protected $appends = [
        'item_name',
        'total_price',
        'location_address',
    ];

    public function getItemNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setItemNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getTotalPriceAttribute(): mixed
    {
        return $this->attributes['total_amount'] ?? 0;
    }

    public function setTotalPriceAttribute(mixed $value): void
    {
        $this->attributes['total_amount'] = $value;
    }

    public function getLocationAddressAttribute(): ?string
    {
        return $this->attributes['location_name'] ?? null;
    }

    public function setLocationAddressAttribute(?string $value): void
    {
        $this->attributes['location_name'] = $value;
    }

    protected function casts(): array
    {
        return [
            'status' => RealizationItemStatus::class,
            'condition' => RealizationItemCondition::class,
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'purchase_date' => 'date',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(RealizationPackage::class, 'realization_package_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(ProposalBudgetItem::class, 'proposal_budget_item_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RealizationItemHistory::class, 'realization_item_id')->latest('created_at');
    }

    public function handovers(): BelongsToMany
    {
        return $this->belongsToMany(Handover::class, 'handover_items', 'realization_item_id', 'handover_id')
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function surveyFindings(): HasMany
    {
        return $this->hasMany(FieldSurveyFinding::class, 'realization_item_id');
    }

    public function monitoringItems(): HasMany
    {
        return $this->hasMany(MonitoringItem::class, 'realization_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
