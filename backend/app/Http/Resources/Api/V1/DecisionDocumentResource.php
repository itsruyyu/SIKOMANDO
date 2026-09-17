<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecisionDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'decision_id' => $this->decision_id,
            'document_type' => $this->document_type,
            'document_title' => $this->document_title,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'mime_type' => $this->mime_type,
            'file_size' => (int) $this->file_size,
            'file_hash' => $this->file_hash,
            'version' => (int) $this->version,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'generated_at' => $this->generated_at?->toISOString(),
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'notes' => $this->notes,
            'download_url' => url(sprintf('/api/v1/decisions/%s/documents/%s/download', $this->decision_id, $this->id)),
            'template' => $this->whenLoaded('template', fn () => [
                'id' => $this->template?->id,
                'code' => $this->template?->code,
                'name' => $this->template?->name,
            ]),
            'template_version' => $this->whenLoaded('templateVersion', fn () => [
                'id' => $this->templateVersion?->id,
                'version_number' => $this->templateVersion?->version_number,
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

