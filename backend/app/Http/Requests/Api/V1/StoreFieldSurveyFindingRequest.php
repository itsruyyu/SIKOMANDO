<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FieldSurveyFindingSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFieldSurveyFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'max:5000',
            ],
            'severity' => [
                'nullable',
                'string',
                Rule::in(array_column(FieldSurveyFindingSeverity::cases(), 'value')),
            ],
            'finding_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'recommended_action' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul temuan survei wajib diisi.',
            'description.required' => 'Deskripsi temuan survei wajib diisi.',
            'severity.in' => 'Tingkat keparahan (severity) harus salah satu dari: low, medium, high, critical.',
            'title.max' => 'Judul temuan maksimal 255 karakter.',
            'description.max' => 'Deskripsi temuan maksimal 5000 karakter.',
        ];
    }
}
