<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreFieldSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'surveyor_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
            'scheduled_date' => [
                'nullable',
                'date',
            ],
            'location_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'location_address' => [
                'nullable',
                'string',
                'max:1000',
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
            'surveyor_id.required' => 'Petugas surveyor wajib dipilih.',
            'surveyor_id.uuid' => 'ID surveyor tidak valid.',
            'surveyor_id.exists' => 'Petugas surveyor tidak ditemukan dalam sistem.',
            'scheduled_date.date' => 'Tanggal jadwal survei harus berupa format tanggal yang valid.',
            'latitude.between' => 'Latitude harus berada dalam rentang -90 hingga 90 derajat.',
            'longitude.between' => 'Longitude harus berada dalam rentang -180 hingga 180 derajat.',
            'notes.max' => 'Catatan survei maksimal 5000 karakter.',
        ];
    }
}
