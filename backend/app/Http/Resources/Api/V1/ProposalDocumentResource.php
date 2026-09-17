<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProposalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProposalDocument
 */
class ProposalDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'document_type_id' => $this->document_type_id,
            'document_requirement_id' => $this->document_requirement_id,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'storage_disk' => $this->storage_disk,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size !== null ? (int) $this->file_size : null,
            'file_hash' => $this->file_hash,
            'version' => (int) $this->version,
            'status' => $this->status,
            'notes' => $this->notes,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => $this->uploaded_at?->toISOString(),
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),
            'download_url' => url("/api/v1/proposals/{$this->proposal_id}/documents/{$this->id}/download"),
            'document_type' => $this->whenLoaded('documentType', fn () => [
                'id' => $this->documentType?->id,
                'name' => $this->documentType?->name,
                'code' => $this->documentType?->code,
            ]),
            'document_requirement' => $this->whenLoaded('documentRequirement', fn () => [
                'id' => $this->documentRequirement?->id,
                'name' => $this->documentRequirement?->name,
                'is_required' => $this->documentRequirement?->is_required,
            ]),
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
                'email' => $this->uploader?->email,
            ]),
            'verifier' => $this->whenLoaded('verifier', fn () => [
                'id' => $this->verifier?->id,
                'name' => $this->verifier?->name,
                'email' => $this->verifier?->email,
            ]),
            'versions' => ProposalDocumentVersionResource::collection($this->whenLoaded('versions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
