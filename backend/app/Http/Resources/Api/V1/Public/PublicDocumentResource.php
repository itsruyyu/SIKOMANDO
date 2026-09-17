<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? $this->id,
            'requirement_id' => $this->resource['requirement_id'] ?? null,
            'requirement_name' => $this->resource['requirement_name'] ?? null,
            'requirement_code' => $this->resource['requirement_code'] ?? null,
            'document_type_id' => $this->resource['document_type_id'] ?? null,
            'document_type_name' => $this->resource['document_type_name'] ?? null,
            'document_type_code' => $this->resource['document_type_code'] ?? null,
            'scope' => $this->resource['scope'] ?? 'proposal',
            'is_mandatory' => (bool) ($this->resource['is_mandatory'] ?? false),
            'maximum_files' => (int) ($this->resource['maximum_files'] ?? 1),
            'description' => $this->resource['description'] ?? null,
        ];
    }
}

