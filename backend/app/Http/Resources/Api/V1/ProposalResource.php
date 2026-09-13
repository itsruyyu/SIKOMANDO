<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_number' => $this->proposal_number,
            'grant_program_id' => $this->grant_program_id,
            'organization_id' => $this->organization_id,
            'applicant_id' => $this->applicant_id,
            'title' => $this->title,
            'background' => $this->background,
            'objectives' => $this->objectives,
            'benefits' => $this->benefits,
            'activities' => $this->activities,
            'expected_outputs' => $this->expected_outputs,
            'requested_amount' => $this->requested_amount,
            'approved_amount' => $this->approved_amount,
            'status' => $this->status?->value ?? $this->status,
            'revision_count' => $this->revision_count,
            'submitted_at' => $this->submitted_at,
            'verified_at' => $this->verified_at,
            'approved_at' => $this->approved_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}