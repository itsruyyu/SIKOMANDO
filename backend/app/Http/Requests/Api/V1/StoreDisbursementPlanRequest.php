<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisbursementPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'planned_amount' => [
                'nullable',
                'numeric',
                'min:1',
            ],
            'planned_date' => [
                'nullable',
                'date',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'bank_account_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'bank_account_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'stages' => [
                'nullable',
                'array',
                'min:1',
            ],
            'stages.*.stage_number' => [
                'required_with:stages',
                'integer',
                'min:1',
            ],
            'stages.*.planned_amount' => [
                'required_with:stages',
                'numeric',
                'min:1',
            ],
            'stages.*.planned_date' => [
                'nullable',
                'date',
            ],
            'stages.*.bank_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'stages.*.bank_account_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'stages.*.bank_account_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'stages.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'planned_amount.numeric' => 'Nominal rencana pencairan harus berupa angka valid.',
            'planned_amount.min' => 'Nominal rencana pencairan minimal 1 rupiah.',
            'stages.*.planned_amount.numeric' => 'Nominal rencana tahap pencairan harus berupa angka valid.',
            'stages.*.planned_amount.min' => 'Nominal rencana tahap pencairan minimal 1 rupiah.',
        ];
    }
}
