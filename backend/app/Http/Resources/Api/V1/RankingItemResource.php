<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ranking_id' => $this->ranking_id,
            'proposal_id' => $this->proposal_id,
            'rank' => $this->rank,
            'evaluation_score' => (float) $this->evaluation_score,
            'survey_score' => (float) $this->survey_score,
            'final_score' => (float) $this->final_score,
            'status' => $this->status,
            'recommendation_result' => $this->recommendation_result,
            'recommendation_reason' => $this->recommendation_reason,
            'notes' => $this->notes,
            'snapshot_data' => $this->snapshot_data,
            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'requested_amount' => $this->proposal?->requested_amount !== null
                    ? (float) $this->proposal?->requested_amount
                    : null,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
