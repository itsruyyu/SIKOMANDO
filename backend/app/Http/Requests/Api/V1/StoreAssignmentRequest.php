<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AssignmentType;
use App\Models\ProposalAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProposalAssignment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'string', 'exists:proposals,id'],
            'assigned_user_id' => ['required', 'string', 'exists:users,id'],
            'assignment_type' => ['required', 'string', Rule::enum(AssignmentType::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
