<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'revision_number' => $this->revision_number,
            'status' => $this->status,
            'reason' => $this->reason,
            'completion_notes' => $this->completion_notes,
            'requested_at' => $this->requested_at,
            'submitted_at' => $this->submitted_at,
            'completed_at' => $this->completed_at,
            'requester' => $this->whenLoaded('requester', function () {
                return [
                    'id' => $this->requester->id,
                    'name' => $this->requester->name,
                    'email' => $this->requester->email,
                ];
            }),
            'items' => RevisionItemResource::collection(
                $this->whenLoaded('items')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
