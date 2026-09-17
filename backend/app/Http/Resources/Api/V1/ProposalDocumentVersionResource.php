<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProposalDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProposalDocumentVersion
 */
class ProposalDocumentVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $proposalId = $this->document?->proposal_id;

        return [
            'id' => $this->id,
            'proposal_document_id' => $this->proposal_document_id,
            'version_number' => (int) $this->version_number,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'storage_disk' => $this->storage_disk,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size !== null ? (int) $this->file_size : null,
            'file_hash' => $this->file_hash,
            'status' => $this->status,
            'change_notes' => $this->change_notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ]),
            'download_url' => $proposalId ? url("/api/v1/proposals/{$proposalId}/documents/{$this->proposal_document_id}/download?version={$this->version_number}") : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
