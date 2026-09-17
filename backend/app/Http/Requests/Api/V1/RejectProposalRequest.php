<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RejectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:5000',
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
            'reason.required' => 'Alasan penolakan proposal wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal 5 karakter.',
            'reason.max' => 'Alasan penolakan maksimal 5000 karakter.',
            'title.max' => 'Judul keputusan maksimal 255 karakter.',
            'summary.max' => 'Ringkasan keputusan maksimal 5000 karakter.',
            'notes.max' => 'Catatan penolakan maksimal 5000 karakter.',
        ];
    }
}
