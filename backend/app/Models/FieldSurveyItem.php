<?php

namespace App\Models;

use App\Enums\FieldSurveyItemResult;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldSurveyItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'field_survey_id',
        'item_code',
        'item_name',
        'description',
        'result',
        'notes',
        'checked_by',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => FieldSurveyItemResult::class,
            'checked_at' => 'immutable_datetime',
        ];
    }

    public function fieldSurvey(): BelongsTo
    {
        return $this->belongsTo(FieldSurvey::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
