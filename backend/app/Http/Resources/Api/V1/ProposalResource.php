<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_number' => $this->proposal_number,

            'grant_program' => $this->whenLoaded(
                'grantProgram',
                fn () => [
                    'id' => $this->grantProgram->id,
                    'code' => $this->grantProgram->code,
                    'name' => $this->grantProgram->name,
                ]
            ),

            'organization' => $this->whenLoaded(
                'organization',
                fn () => [
                    'id' => $this->organization->id,
                    'code' => $this->organization->code,
                    'name' => $this->organization->name,
                ]
            ),

            'applicant' => $this->whenLoaded(
                'applicant',
                fn () => [
                    'id' => $this->applicant->id,
                    'name' => $this->applicant->name,
                    'email' => $this->applicant->email,
                ]
            ),

            'title' => $this->title,
            'background' => $this->background,
            'objectives' => $this->objectives,
            'benefits' => $this->benefits,
            'activities' => $this->activities,
            'expected_outputs' => $this->expected_outputs,

            'requested_amount' => $this->requested_amount,
            'approved_amount' => $this->approved_amount,

            'status' => $this->status instanceof \BackedEnum
                ? $this->status->value
                : $this->status,

            'revision_count' => $this->revision_count,
            'submitted_at' => $this->submitted_at,
            'verified_at' => $this->verified_at,
            'approved_at' => $this->approved_at,
            'completed_at' => $this->completed_at,

            'budget_items' => $this->whenLoaded(
                'budgetItems',
                fn () => $this->budgetItems->map(fn ($item) => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'sort_order' => $item->sort_order,
                ])->values()
            ),

            'documents' => $this->whenLoaded(
                'documents',
                fn () => $this->documents->map(fn ($document) => [
                    'id' => $document->id,
                    'document_type_id' => $document->document_type_id,
                    'original_filename' => $document->original_filename,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'version' => $document->version,
                    'status' => $document->status,
                ])->values()
            ),
        ];
    }
}
