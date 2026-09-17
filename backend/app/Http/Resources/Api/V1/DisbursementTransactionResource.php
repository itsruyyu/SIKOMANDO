<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisbursementTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disbursement_id' => $this->disbursement_id,
            'transaction_number' => $this->transaction_number,
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'transaction_date' => $this->transaction_date?->toDateString(),
            'bank_reference' => $this->bank_reference,
            'recipient_name' => $this->recipient_name,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'notes' => $this->notes,

            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder?->id,
                'name' => $this->recorder?->name,
                'email' => $this->recorder?->email,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
