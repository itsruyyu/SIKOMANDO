<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Receipt extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'receipt_number',
        'proposal_id',
        'disbursement_id',
        'realization_package_id',
        'payer_name',
        'recipient_name',
        'amount',
        'receipt_date',
        'purpose',
        'status',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReceiptStatus::class,
            'amount' => 'decimal:2',
            'receipt_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function realizationPackage(): BelongsTo
    {
        return $this->belongsTo(RealizationPackage::class);
    }

    public function package(): BelongsTo
    {
        return $this->realizationPackage();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(DigitalSignature::class, 'signable');
    }

    public function activeSignature(): MorphOne
    {
        return $this->morphOne(DigitalSignature::class, 'signable')
            ->where('status', 'signed');
    }

    public function getPaidToAttribute(): ?string
    {
        return $this->recipient_name;
    }

    public function setPaidToAttribute(?string $value): void
    {
        $this->attributes['recipient_name'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->purpose;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['purpose'] = $value;
    }
}
