<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ProposalStatus;
use App\Models\LpjSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LpjSubmission
 */
class LpjSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'organization_id' => $this->organization_id,
            'grant_program_id' => $this->grant_program_id,
            'decision_id' => $this->decision_id,
            'disbursement_id' => $this->disbursement_id,
            'stage_number' => $this->stage_number,
            'lpj_number' => $this->lpj_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'total_received' => (float) $this->total_received,
            'total_spent' => (float) $this->total_spent,
            'remaining_balance' => (float) $this->remaining_balance,
            'summary' => $this->summary,
            'notes' => $this->notes,
            'verification_notes' => $this->verification_notes,
            'revision_reason' => $this->revision_reason,
            'rejection_reason' => $this->rejection_reason,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'status' => $this->proposal?->status instanceof ProposalStatus ? $this->proposal->status->value : $this->proposal?->status,
                'applicant_id' => $this->proposal?->applicant_id,
            ]),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'grant_program' => $this->whenLoaded('grantProgram', fn () => [
                'id' => $this->grantProgram?->id,
                'code' => $this->grantProgram?->code,
                'name' => $this->grantProgram?->name,
            ]),
            'submitted_by' => $this->whenLoaded('submittedBy', fn () => [
                'id' => $this->submittedBy?->id,
                'name' => $this->submittedBy?->name,
                'email' => $this->submittedBy?->email,
            ]),
            'verified_by' => $this->whenLoaded('verifiedBy', fn () => [
                'id' => $this->verifiedBy?->id,
                'name' => $this->verifiedBy?->name,
                'email' => $this->verifiedBy?->email,
            ]),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->name,
                'email' => $this->approvedBy?->email,
            ]),
            'items' => LpjItemResource::collection($this->whenLoaded('items')),
            'documents' => LpjDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
