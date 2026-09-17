<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FillFieldSurveyResultRequest extends FormRequest
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
            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.max' => 'Ringkasan hasil survei maksimal 10000 karakter.',
            'recommendation.max' => 'Rekomendasi teknis maksimal 10000 karakter.',
            'notes.max' => 'Catatan maksimal 5000 karakter.',
            'latitude.between' => 'Latitude harus berada dalam rentang -90 hingga 90 derajat.',
            'longitude.between' => 'Longitude harus berada dalam rentang -180 hingga 180 derajat.',
        ];
    }
}
