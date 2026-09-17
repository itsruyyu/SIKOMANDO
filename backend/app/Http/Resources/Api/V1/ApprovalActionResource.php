<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_id' => $this->approval_id,
            'actor_id' => $this->actor_id,
            'level' => (int) $this->level,
            'action' => $this->action,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'acted_at' => $this->acted_at?->toISOString(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor?->id,
                'name' => $this->actor?->name,
                'email' => $this->actor?->email,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

