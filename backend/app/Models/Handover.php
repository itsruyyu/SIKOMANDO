<?php

namespace App\Models;

use App\Enums\HandoverStatus;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Handover extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'handover_number',
        'proposal_id',
        'realization_package_id',
        'handover_date',
        'giver_name',
        'giver_position',
        'recipient_name',
        'recipient_position',
        'status',
        'location',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => HandoverStatus::class,
            'handover_date' => 'date',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function realizationPackage(): BelongsTo
    {
        return $this->belongsTo(RealizationPackage::class);
    }

    public function package(): BelongsTo
    {
        return $this->realizationPackage();
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(RealizationItem::class, 'handover_items', 'handover_id', 'realization_item_id')
            ->using(HandoverItem::class)
            ->withPivot('notes')
            ->withTimestamps();
    }

    public function handoverItems(): HasMany
    {
        return $this->hasMany(HandoverItem::class, 'handover_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(DigitalSignature::class, 'signable');
    }

    public function digitalSignatures(): MorphMany
    {
        return $this->signatures();
    }

    public function activeSignature(): MorphOne
    {
        return $this->morphOne(DigitalSignature::class, 'signable')
            ->where('status', 'signed');
    }

    public function getHandoverByNameAttribute(): ?string
    {
        return $this->giver_name;
    }

    public function setHandoverByNameAttribute(?string $value): void
    {
        $this->attributes['giver_name'] = $value;
    }

    public function getHandoverByPositionAttribute(): ?string
    {
        return $this->giver_position;
    }

    public function setHandoverByPositionAttribute(?string $value): void
    {
        $this->attributes['giver_position'] = $value;
    }

    public function getHandoverToNameAttribute(): ?string
    {
        return $this->recipient_name;
    }

    public function setHandoverToNameAttribute(?string $value): void
    {
        $this->attributes['recipient_name'] = $value;
    }

    public function getHandoverToPositionAttribute(): ?string
    {
        return $this->recipient_position;
    }

    public function setHandoverToPositionAttribute(?string $value): void
    {
        $this->attributes['recipient_position'] = $value;
    }
}
