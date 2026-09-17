<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recommendation_id' => $this->recommendation_id,
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'result' => $this->result,
            'description' => $this->description,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
