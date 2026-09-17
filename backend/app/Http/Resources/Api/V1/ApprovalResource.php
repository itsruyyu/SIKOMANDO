<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'recommendation_id' => $this->recommendation_id,
            'approval_number' => $this->approval_number,
            'approval_type' => $this->approval_type,
            'required_levels' => (int) $this->required_levels,
            'current_level' => (int) $this->current_level,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'summary' => $this->summary,
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'requested_amount' => (float) $this->proposal?->requested_amount,
                'status' => $this->proposal?->status?->value ?? (string) $this->proposal?->status,
                'organization' => $this->proposal?->relationLoaded('organization') && $this->proposal->organization ? [
                    'id' => $this->proposal->organization->id,
                    'name' => $this->proposal->organization->name,
                ] : null,
                'grant_program' => $this->proposal?->relationLoaded('grantProgram') && $this->proposal->grantProgram ? [
                    'id' => $this->proposal->grantProgram->id,
                    'code' => $this->proposal->grantProgram->code,
                    'name' => $this->proposal->grantProgram->name,
                ] : null,
            ]),

            'recommendation' => $this->whenLoaded('recommendation', fn () => [
                'id' => $this->recommendation?->id,
                'recommendation_number' => $this->recommendation?->recommendation_number,
                'status' => $this->recommendation?->status?->value ?? (string) $this->recommendation?->status,
                'result' => $this->recommendation?->result?->value ?? (string) $this->recommendation?->result,
                'recommended_amount' => (float) $this->recommendation?->recommended_amount,
                'summary' => $this->recommendation?->summary,
                'reason' => $this->recommendation?->reason,
            ]),

            'actions' => ApprovalActionResource::collection(
                $this->whenLoaded('actions')
            ),

            'decision' => new DecisionResource(
                $this->whenLoaded('decision')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
