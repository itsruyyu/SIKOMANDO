<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RejectLpjRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lpj = $this->route('lpj');

        return $lpj ? ($this->user()?->can('reject', $lpj) ?? false) : true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
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
            'reason.required' => 'Alasan penolakan LPJ wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal :min karakter.',
        ];
    }
}
