<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'evaluator_id' => $this->evaluator_id,
            'evaluation_number' => $this->evaluation_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'total_score' => $this->total_score !== null
                ? (float) $this->total_score
                : null,
            'final_score' => $this->final_score !== null
                ? (float) $this->final_score
                : null,
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'summary' => $this->summary,
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'items' => EvaluationItemResource::collection(
                $this->whenLoaded('items')
            ),

            'evaluator' => $this->whenLoaded(
                'evaluator',
                fn () => [
                    'id' => $this->evaluator?->id,
                    'name' => $this->evaluator?->name,
                    'email' => $this->evaluator?->email,
                ]
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
