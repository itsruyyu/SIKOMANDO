<?php

namespace App\Models;

use App\Enums\FieldSurveyResult;
use App\Enums\FieldSurveyStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldSurvey extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'proposal_id',
        'surveyor_id',
        'survey_number',
        'status',
        'result',
        'scheduled_date',
        'started_at',
        'completed_at',
        'location_name',
        'location_address',
        'latitude',
        'longitude',
        'summary',
        'recommendation',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => FieldSurveyStatus::class,
            'result' => FieldSurveyResult::class,
            'scheduled_date' => 'date',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FieldSurveyItem::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(FieldSurveyFinding::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FieldSurveyDocument::class);
    }
}
