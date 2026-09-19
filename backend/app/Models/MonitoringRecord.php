<?php

namespace App\Models;

use App\Models\Concerns\HasQrIdentity;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringRecord extends Model
{
    use HasFactory, HasQrIdentity, HasUuid;

    protected $fillable = [
        'proposal_id',
        'created_by',
        'monitoring_number',
        'monitoring_type',
        'status',
        'monitoring_date',
        'progress_percentage',
        'financial_progress_percentage',
        'summary',
        'findings',
        'recommendations',
        'notes',
        'submitted_at',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_date' => 'date',
            'progress_percentage' => 'decimal:2',
            'financial_progress_percentage' => 'decimal:2',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspector(): BelongsTo
    {
        return $this->creator();
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonitoringItem::class, 'monitoring_record_id');
    }

    public function getTitleAttribute(): ?string
    {
        return $this->summary;
    }

    public function setTitleAttribute(?string $value): void
    {
        $this->attributes['summary'] = $value;
    }

    public function getInspectorIdAttribute(): ?string
    {
        return $this->created_by;
    }

    public function setInspectorIdAttribute(?string $value): void
    {
        $this->attributes['created_by'] = $value;
    }

    public function getOverallResultAttribute(): ?string
    {
        return $this->findings;
    }

    public function setOverallResultAttribute(?string $value): void
    {
        $this->attributes['findings'] = $value;
    }
}
