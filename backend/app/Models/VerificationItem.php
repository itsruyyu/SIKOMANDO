<?php

namespace App\Models;

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
            'checked_at' => 'immutable_datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(Verification::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}