<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompleteEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.string' => 'Ringkasan harus berupa teks.',
            'summary.max' => 'Ringkasan maksimal 5000 karakter.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal 5000 karakter.',
        ];
    }
}
