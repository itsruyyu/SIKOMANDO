<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class StoreRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal instanceof Proposal
            && $this->user()?->can('verify', $proposal);
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.item_code' => [
                'nullable',
                'string',
                'max:100',
            ],
            'items.*.field_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'items.*.description' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.old_value' => [
                'nullable',
                'string',
            ],
            'items.*.new_value' => [
                'nullable',
                'string',
            ],
            'items.*.notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}