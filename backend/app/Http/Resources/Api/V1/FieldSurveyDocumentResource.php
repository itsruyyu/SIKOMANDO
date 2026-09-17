<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSurveyDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_survey_id' => $this->field_survey_id,
            'document_type_id' => $this->document_type_id,
            'uploaded_by' => $this->uploaded_by,
            'document_title' => $this->document_title,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size !== null ? (int) $this->file_size : null,
            'version' => $this->version !== null ? (int) $this->version : null,
            'status' => $this->status,
            'notes' => $this->notes,
            'document_type' => $this->whenLoaded('documentType', fn () => [
                'id' => $this->documentType?->id,
                'name' => $this->documentType?->name,
                'code' => $this->documentType?->code,
            ]),
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
                'email' => $this->uploader?->email,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
