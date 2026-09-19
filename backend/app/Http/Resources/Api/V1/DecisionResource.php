<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'approval_id' => $this->approval_id,
            'decision_number' => $this->decision_number,
            'decision_type' => $this->decision_type,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'decision_date' => $this->decision_date?->toDateString(),
            'approved_amount' => (float) $this->approved_amount,
            'amount' => (float) ($this->approved_amount ?? 0),
            'title' => $this->title,
            'summary' => $this->summary,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'issued_at' => $this->issued_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,

            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'requested_amount' => (float) $this->proposal?->requested_amount,
                'approved_amount' => (float) ($this->proposal?->approved_amount ?? $this->approved_amount ?? 0),
                'status' => $this->proposal?->status?->value ?? (string) $this->proposal?->status,
                'organization' => $this->proposal?->relationLoaded('organization') && $this->proposal->organization ? [
                    'id' => $this->proposal->organization->id,
                    'name' => $this->proposal->organization->name,
                ] : null,
                'grant_program' => $this->proposal?->relationLoaded('grantProgram') && $this->proposal->grantProgram ? [
                    'id' => $this->proposal->grantProgram->id,
                    'code' => $this->proposal->grantProgram->code,
                    'name' => $this->proposal->grantProgram->name,
                ] : null,
            ]),

            'issuer' => $this->whenLoaded('issuer', fn () => [
                'id' => $this->issuer?->id,
                'name' => $this->issuer?->name,
                'email' => $this->issuer?->email,
            ]),

            'documents' => DecisionDocumentResource::collection(
                $this->whenLoaded('documents')
            ),

            'qr' => ($qr = \App\Models\QrIdentity::where('qrable_type', get_class($this->resource))
                ->where('qrable_id', $this->id)
                ->where('status', \App\Enums\QrStatus::ACTIVE)
                ->first()) ? [
                'id' => $qr->id,
                'token' => $qr->token,
                'svg_url' => asset('storage/' . $qr->qr_code_path),
                'verification_url' => $qr->verification_url,
            ] : null,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
