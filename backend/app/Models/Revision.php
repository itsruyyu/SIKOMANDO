<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Revision extends Model
{
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'requested_by',
        'revision_number',
        'status',
        'reason',
        'completion_notes',
        'requested_at',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'requested_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RevisionItem::class);
    }
}