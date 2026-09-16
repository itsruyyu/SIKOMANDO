<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'verification_id' => $this->verification_id,
            'requirement_id' => $this->requirement_id,
            'document_type_id' => $this->document_type_id,
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'notes' => $this->notes,
            'checked_by' => $this->checked_by,
            'checked_at' => $this->checked_at?->toISOString(),
        ];
    }
}
