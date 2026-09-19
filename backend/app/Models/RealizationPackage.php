<?php

namespace App\Models;

use App\Enums\RealizationPackageStatus;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RealizationPackage extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'proposal_id',
        'package_number',
        'name',
        'package_name',
        'description',
        'status',
        'package_date',
        'total_amount',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $appends = [
        'package_name',
    ];

    public function getPackageNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setPackageNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    protected function casts(): array
    {
        return [
            'status' => RealizationPackageStatus::class,
            'package_date' => 'date',
            'total_amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RealizationItem::class, 'realization_package_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'realization_package_id');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class, 'realization_package_id');
    }
}
