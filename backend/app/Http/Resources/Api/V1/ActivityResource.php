<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'module' => $this->module,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'request_id' => $this->request_id,

            'actor' => $this->whenLoaded('actor', function (): ?array {
                if (! $this->actor) {
                    return null;
                }

                return [
                    'id' => $this->actor->id,
                    'name' => $this->actor->name,
                ];
            }),

            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
