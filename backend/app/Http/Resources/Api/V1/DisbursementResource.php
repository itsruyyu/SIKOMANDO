<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisbursementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disbursement_plan_id' => $this->disbursement_plan_id,
            'proposal_id' => $this->proposal_id,
            'stage_number' => (int) $this->stage_number,
            'disbursement_number' => $this->disbursement_number,
            'planned_amount' => (float) $this->planned_amount,
            'approved_amount' => $this->approved_amount !== null ? (float) $this->approved_amount : null,
            'paid_amount' => (float) $this->paid_amount,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'planned_date' => $this->planned_date?->toDateString(),
            'approved_date' => $this->approved_date?->toDateString(),
            'paid_date' => $this->paid_date?->toDateString(),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'notes' => $this->notes,

            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'status' => $this->proposal?->status?->value ?? (string) $this->proposal?->status,
                'requested_amount' => (float) $this->proposal?->requested_amount,
                'approved_amount' => (float) $this->proposal?->approved_amount,
                'organization' => $this->proposal?->relationLoaded('organization') && $this->proposal->organization ? [
                    'id' => $this->proposal->organization->id,
                    'name' => $this->proposal->organization->name,
                    'phone' => $this->proposal->organization->phone,
                    'email' => $this->proposal->organization->email,
                    'address' => $this->proposal->organization->address,
                ] : null,
                'grant_program' => $this->proposal?->relationLoaded('grantProgram') && $this->proposal->grantProgram ? [
                    'id' => $this->proposal->grantProgram->id,
                    'code' => $this->proposal->grantProgram->code,
                    'name' => $this->proposal->grantProgram->name,
                    'fiscal_year' => $this->proposal->grantProgram->fiscal_year,
                ] : null,
            ]),

            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan?->id,
                'plan_number' => $this->plan?->plan_number,
                'total_stages' => (int) $this->plan?->total_stages,
                'planned_amount' => (float) $this->plan?->planned_amount,
                'status' => $this->plan?->status?->value,
                'creator' => $this->plan?->relationLoaded('creator') && $this->plan->creator ? [
                    'id' => $this->plan->creator->id,
                    'name' => $this->plan->creator->name,
                    'email' => $this->plan->creator->email,
                ] : null,
            ]),

            'transactions' => DisbursementTransactionResource::collection(
                $this->whenLoaded('transactions')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
