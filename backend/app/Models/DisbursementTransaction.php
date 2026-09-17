<?php

namespace App\Models;

use App\Enums\DisbursementTransactionStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementTransaction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'disbursement_id',
        'recorded_by',
        'transaction_number',
        'transaction_type',
        'amount',
        'status',
        'transaction_date',
        'bank_reference',
        'recipient_name',
        'bank_name',
        'bank_account_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisbursementTransactionStatus::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
