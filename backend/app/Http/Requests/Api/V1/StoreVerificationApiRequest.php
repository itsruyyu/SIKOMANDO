<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Proposal;
use App\Models\Verification;
use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal instanceof Proposal
            && $this->user()?->can('create', [
                Verification::class,
                $proposal,
            ]);
    }

    public function rules(): array
    {
        return [
            'summary' => [
                'nullable',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
