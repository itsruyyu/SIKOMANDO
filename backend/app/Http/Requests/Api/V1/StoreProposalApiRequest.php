<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class StoreProposalApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Proposal::class)
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
            'expected_outputs' => [
                'nullable',
                'string',
            ],
            'budget_items' => [
                'nullable',
                'array',
            ],
            'budget_items.*.category' => [
                'required_with:budget_items',
                'string',
            ],
            'budget_items.*.item_name' => [
                'required_with:budget_items',
                'string',
            ],
            'budget_items.*.description' => [
                'nullable',
                'string',
            ],
            'budget_items.*.quantity' => [
                'required_with:budget_items',
                'numeric',
                'min:0.01',
            ],
            'budget_items.*.unit' => [
                'required_with:budget_items',
                'string',
            ],
            'budget_items.*.unit_price' => [
                'required_with:budget_items',
                'numeric',
                'min:0',
            ],
            'budget_items.*.sort_order' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
