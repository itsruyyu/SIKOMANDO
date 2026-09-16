<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'verifier_id' => $this->verifier_id,
            'verification_number' => $this->verification_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'summary' => $this->summary,
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'items' => VerificationItemResource::collection(
                $this->whenLoaded('items')
            ),
            'verifier' => $this->whenLoaded('verifier', fn () => [
                'id' => $this->verifier?->id,
                'name' => $this->verifier?->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
