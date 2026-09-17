<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evaluation_id' => $this->evaluation_id,
            'evaluation_criteria_id' => $this->evaluation_criteria_id,
            'weight' => $this->weight !== null
                ? (float) $this->weight
                : null,
            'score' => $this->score !== null
                ? (float) $this->score
                : null,
            'weighted_score' => $this->weighted_score !== null
                ? (float) $this->weighted_score
                : null,
            'minimum_score' => $this->minimum_score !== null
                ? (float) $this->minimum_score
                : null,
            'maximum_score' => $this->maximum_score !== null
                ? (float) $this->maximum_score
                : null,
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'notes' => $this->notes,
            'scored_at' => $this->scored_at?->toISOString(),
            'criteria' => $this->whenLoaded('criteria', fn () => [
                'id' => $this->criteria?->id,
                'code' => $this->criteria?->code,
                'name' => $this->criteria?->name,
                'description' => $this->criteria?->description,
                'criterion_type' => $this->criteria?->criterion_type,
            ]),
        ];
    }
}
