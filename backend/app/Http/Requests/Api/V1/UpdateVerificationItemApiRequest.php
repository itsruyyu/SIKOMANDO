<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\VerificationItemResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationItemApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $verification = $this->route('verification');

        return $verification
            && $this->user()?->can('update', $verification);
    }

    public function rules(): array
    {
        return [
            'result' => [
                'required',
                'string',
                Rule::enum(VerificationItemResult::class),
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
