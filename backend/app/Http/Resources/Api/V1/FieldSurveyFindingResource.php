<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSurveyFindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_survey_id' => $this->field_survey_id,
            'finding_code' => $this->finding_code,
            'finding_type' => $this->finding_type,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),
            'recommended_action' => $this->recommended_action,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'realization_item_id' => $this->realization_item_id,
            'scanned_qr_token' => $this->scanned_qr_token,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
