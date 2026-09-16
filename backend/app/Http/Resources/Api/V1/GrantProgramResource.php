<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrantProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'fiscal_year' => $this->fiscal_year,
            'status' => $this->status,
            'registration_start_at' => $this->registration_start_at,
            'registration_end_at' => $this->registration_end_at,
            'minimum_amount' => $this->minimum_amount,
            'maximum_amount' => $this->maximum_amount,
            'total_budget' => $this->total_budget,
            'is_active' => $this->is_active,
        ];
    }
}
