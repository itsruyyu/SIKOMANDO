<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Proposal::class)
            ?? false;
    }

    public function rules(): array
    {
        return [
            'grant_program_id' => [
                'required',
                'uuid',
                'exists:grant_programs,id',
            ],
            'organization_id' => [
                'required',
                'uuid',
                'exists:organizations,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'background' => [
                'nullable',
                'string',
            ],
            'objectives' => [
                'nullable',
                'string',
            ],
            'benefits' => [
                'nullable',
                'string',
            ],
            'activities' => [
                'nullable',
                'string',
            ],
            'outputs' => [
                'nullable',
                'string',
            ],
        ];
    }
}