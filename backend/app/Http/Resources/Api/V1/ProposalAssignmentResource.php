<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal->id,
                'title' => $this->proposal->title,
                'proposal_number' => $this->proposal->proposal_number,
                'status' => $this->proposal->status?->value,
            ]),
            'assigned_user_id' => $this->assigned_user_id,
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
                'email' => $this->assignedUser->email,
            ]),
            'assignment_type' => $this->assignment_type?->value,
            'assignment_type_label' => $this->assignment_type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'assigned_by' => $this->whenLoaded('assigner', fn () => [
                'id' => $this->assigner->id,
                'name' => $this->assigner->name,
            ]),
            'assigned_at' => $this->assigned_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'revoked_by' => $this->whenLoaded('revoker', fn () => [
                'id' => $this->revoker->id,
                'name' => $this->revoker->name,
            ]),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
