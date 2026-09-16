<?php

namespace App\Models;

use App\Enums\VerificationItemResult;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'verification_id',
        'requirement_id',
        'document_type_id',
        'checked_by',
        'item_code',
        'item_name',
        'result',
        'notes',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => VerificationItemResult::class,
            'checked_at' => 'immutable_datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(Verification::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
