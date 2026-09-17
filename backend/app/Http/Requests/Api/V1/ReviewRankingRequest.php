<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRankingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => [
                'nullable',
                'string',
                Rule::in(['reviewed']),
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
            'action.in' => 'Aksi review harus bernilai reviewed.',
            'notes.max' => 'Catatan review maksimal 5000 karakter.',
        ];
    }
}
