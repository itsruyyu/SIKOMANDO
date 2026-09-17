<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSurveyItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_survey_id' => $this->field_survey_id,
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'description' => $this->description,
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'notes' => $this->notes,
            'checked_at' => $this->checked_at?->toISOString(),
            'checked_by' => $this->checked_by,
            'checker' => $this->whenLoaded('checker', fn () => [
                'id' => $this->checker?->id,
                'name' => $this->checker?->name,
                'email' => $this->checker?->email,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
