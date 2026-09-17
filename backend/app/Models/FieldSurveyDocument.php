<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldSurveyDocument extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'field_survey_id',
        'document_type_id',
        'uploaded_by',
        'document_title',
        'original_filename',
        'stored_filename',
        'disk',
        'storage_path',
        'mime_type',
        'file_size',
        'file_hash',
        'version',
        'status',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    public function fieldSurvey(): BelongsTo
    {
        return $this->belongsTo(FieldSurvey::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
