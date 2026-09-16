<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationDocument extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'document_type_id',
        'document_number',
        'document_date',
        'expiry_date',
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
        'uploaded_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'immutable_date',
            'expiry_date' => 'immutable_date',
            'file_size' => 'integer',
            'version' => 'integer',
            'verified_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
