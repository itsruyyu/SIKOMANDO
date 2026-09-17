<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approved_amount' => [
                'nullable',
                'numeric',
                'min:1',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'approved_amount.numeric' => 'Nominal yang disetujui harus berupa angka valid.',
            'approved_amount.min' => 'Nominal yang disetujui minimal 1 rupiah.',
            'notes.max' => 'Catatan persetujuan maksimal 2000 karakter.',
        ];
    }
}
