<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Models\GrantProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GrantProgram
 */
class PublicGrantProgramResource extends JsonResource
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
            'registration_start_at' => $this->registration_start_at?->toISOString(),
            'registration_end_at' => $this->registration_end_at?->toISOString(),
            'minimum_amount' => $this->minimum_amount !== null ? (float) $this->minimum_amount : null,
            'maximum_amount' => $this->maximum_amount !== null ? (float) $this->maximum_amount : null,
            'total_budget' => $this->total_budget !== null ? (float) $this->total_budget : null,
            'is_active' => (bool) $this->is_active,
            'timeline_url' => url("/api/v1/public/grant-programs/{$this->id}/timeline"),
            'documents_url' => url("/api/v1/public/grant-programs/{$this->id}/documents"),
            'requirements' => $this->whenLoaded('documentRequirements', fn () => $this->documentRequirements->map(fn ($req) => [
                'id' => $req->id,
                'requirement_name' => $req->requirement?->name,
                'document_type_name' => $req->documentType?->name,
                'document_type_code' => $req->documentType?->code,
                'scope' => $req->scope,
                'is_mandatory' => (bool) $req->is_mandatory,
                'maximum_files' => (int) ($req->maximum_files ?? 1),
            ])),
        ];
    }
}

