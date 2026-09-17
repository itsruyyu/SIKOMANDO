<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'surveyor_id' => $this->surveyor_id,
            'survey_number' => $this->survey_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'result' => $this->result?->value,
            'result_label' => $this->result?->label(),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'location_name' => $this->location_name,
            'location_address' => $this->location_address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'summary' => $this->summary,
            'recommendation' => $this->recommendation,
            'notes' => $this->notes,

            'proposal' => $this->whenLoaded('proposal', fn () => [
                'id' => $this->proposal?->id,
                'proposal_number' => $this->proposal?->proposal_number,
                'title' => $this->proposal?->title,
                'status' => $this->proposal?->status?->value,
            ]),

            'items' => FieldSurveyItemResource::collection(
                $this->whenLoaded('items')
            ),

            'findings' => FieldSurveyFindingResource::collection(
                $this->whenLoaded('findings')
            ),

            'documents' => FieldSurveyDocumentResource::collection(
                $this->whenLoaded('documents')
            ),

            'surveyor' => $this->whenLoaded('surveyor', fn () => [
                'id' => $this->surveyor?->id,
                'name' => $this->surveyor?->name,
                'email' => $this->surveyor?->email,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
