<?php

namespace App\Models;

use App\Enums\FieldSurveyFindingSeverity;
use App\Enums\FieldSurveyFindingStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldSurveyFinding extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'field_survey_id',
        'finding_code',
        'finding_type',
        'title',
        'description',
        'severity',
        'recommended_action',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => FieldSurveyFindingSeverity::class,
            'status' => FieldSurveyFindingStatus::class,
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function fieldSurvey(): BelongsTo
    {
        return $this->belongsTo(FieldSurvey::class);
    }
}
