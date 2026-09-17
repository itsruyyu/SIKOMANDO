<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FieldSurveyItemResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldSurveyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result' => [
                'required',
                'string',
                Rule::in(array_column(FieldSurveyItemResult::cases(), 'value')),
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
            'result.required' => 'Hasil pemeriksaan item wajib diisi.',
            'result.in' => 'Hasil pemeriksaan item harus salah satu dari: pass, fail, pending, not_applicable.',
            'notes.max' => 'Catatan item maksimal 5000 karakter.',
        ];
    }
}
