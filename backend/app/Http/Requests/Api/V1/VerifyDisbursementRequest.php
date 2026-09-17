<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Catatan verifikasi maksimal 2000 karakter.',
        ];
    }
}
