<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalStatusHistory extends Model
{
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'notes',
        'request_id',
        'changed_at',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'changed_at' => 'immutable_datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}