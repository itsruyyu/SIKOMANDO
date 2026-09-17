<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpjDocument extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'lpj_submission_id',
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
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LpjSubmission::class, 'lpj_submission_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
