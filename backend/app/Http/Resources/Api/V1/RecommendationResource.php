<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'recommended_by' => $this->recommended_by,
            'recommendation_number' => $this->recommendation_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'recommended_amount' => $this->recommended_amount !== null ? (float) $this->recommended_amount : null,
            'summary' => $this->summary,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'proposed_amount' => $this->proposal?->proposed_amount !== null ? (float) $this->proposal?->proposed_amount : null,
            ]),

            'recommender' => $this->whenLoaded('recommender', fn () => [
                'id' => $this->recommender?->id,
                'name' => $this->recommender?->name,
                'email' => $this->recommender?->email,
            ]),

            'items' => RecommendationItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
