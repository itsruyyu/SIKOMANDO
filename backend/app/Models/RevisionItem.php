<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'revision_id',
        'item_code',
        'field_name',
        'description',
        'old_value',
        'new_value',
        'status',
        'notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class);
    }
}