<?php

namespace App\Models;

use App\Enums\DecisionDocumentStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DecisionDocument extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'decision_id',
        'decision_template_id',
        'decision_template_version_id',
        'uploaded_by',
        'document_type',
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
        'generated_at',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DecisionDocumentStatus::class,
            'version' => 'integer',
            'file_size' => 'integer',
            'generated_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DecisionTemplate::class, 'decision_template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DecisionTemplateVersion::class, 'decision_template_version_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DecisionDocumentVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DecisionDocumentVersion::class)->orderByDesc('created_at');
    }
}
