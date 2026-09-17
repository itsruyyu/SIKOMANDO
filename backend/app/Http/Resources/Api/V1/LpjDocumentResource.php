<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LpjDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LpjDocument
 */
class LpjDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lpj_submission_id' => $this->lpj_submission_id,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
                'email' => $this->uploader?->email,
            ]),
            'document_type' => $this->document_type,
            'document_title' => $this->document_title,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'disk' => $this->disk,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_hash' => $this->file_hash,
            'version' => $this->version,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
