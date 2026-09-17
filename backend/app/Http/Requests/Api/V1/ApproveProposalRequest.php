<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApproveProposalRequest extends FormRequest
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
                'min:0',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
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
            'approved_amount.numeric' => 'Besaran nominal yang disetujui harus berupa angka valid.',
            'approved_amount.min' => 'Besaran nominal yang disetujui tidak boleh negatif.',
            'title.max' => 'Judul keputusan maksimal 255 karakter.',
            'summary.max' => 'Ringkasan keputusan maksimal 5000 karakter.',
            'notes.max' => 'Catatan keputusan maksimal 5000 karakter.',
        ];
    }
}
