<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grant_program_id' => $this->grant_program_id,
            'ranking_number' => $this->ranking_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'weights_snapshot' => $this->weights_snapshot,
            'cutoff_score' => $this->cutoff_score !== null ? (float) $this->cutoff_score : null,
            'total_proposals' => (int) $this->total_proposals,
            'recommended_count' => (int) $this->recommended_count,
            'not_recommended_count' => (int) $this->not_recommended_count,
            'summary' => $this->summary,
            'notes' => $this->notes,
            'generated_at' => $this->generated_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'finalized_at' => $this->finalized_at?->toISOString(),

            'grant_program' => $this->whenLoaded('grantProgram', fn () => [
                'id' => $this->grantProgram?->id,
                'code' => $this->grantProgram?->code,
                'name' => $this->grantProgram?->name,
                'fiscal_year' => $this->grantProgram?->fiscal_year,
            ]),

            'generator' => $this->whenLoaded('generator', fn () => [
                'id' => $this->generator?->id,
                'name' => $this->generator?->name,
                'email' => $this->generator?->email,
            ]),

            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
                'email' => $this->reviewer?->email,
            ]),

            'finalizer' => $this->whenLoaded('finalizer', fn () => [
                'id' => $this->finalizer?->id,
                'name' => $this->finalizer?->name,
                'email' => $this->finalizer?->email,
            ]),

            'items' => RankingItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
