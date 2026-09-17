<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewFieldSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['reviewed', 'request_revision']),
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
            'action.required' => 'Aksi review wajib ditentukan (reviewed atau request_revision).',
            'action.in' => 'Aksi review harus salah satu dari: reviewed, request_revision.',
            'notes.max' => 'Catatan review maksimal 5000 karakter.',
        ];
    }
}
