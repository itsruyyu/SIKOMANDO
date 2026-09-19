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

    protected function prepareForValidation(): void
    {
        $raw = $this->input('result') ?: $this->input('status');
        $mapped = match ($raw) {
            'valid' => 'pass',
            'invalid' => 'fail',
            'revision_required' => 'need_revision',
            default => $raw,
        };

        if ($mapped) {
            $this->merge(['result' => $mapped]);
        }
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
