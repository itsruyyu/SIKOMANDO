<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FieldSurveyResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteFieldSurveyRequest extends FormRequest
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
                Rule::in([
                    FieldSurveyResult::RECOMMENDED->value,
                    FieldSurveyResult::NOT_RECOMMENDED->value,
                ]),
            ],
            'summary' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'recommendation' => [
                'nullable',
                'string',
                'max:10000',
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
            'result.required' => 'Hasil akhir survei wajib ditentukan.',
            'result.in' => 'Hasil akhir survei harus berupa recommended atau not_recommended.',
            'summary.max' => 'Ringkasan survei maksimal 10000 karakter.',
            'recommendation.max' => 'Rekomendasi teknis maksimal 10000 karakter.',
            'notes.max' => 'Catatan maksimal 5000 karakter.',
        ];
    }
}
