<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLpjRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'items' => [
                'nullable',
                'array',
            ],
            'items.*.category' => [
                'required_with:items',
                'string',
                'max:150',
            ],
            'items.*.item_name' => [
                'required_with:items',
                'string',
                'max:255',
            ],
            'items.*.budget_item' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.quantity' => [
                'required_with:items',
                'numeric',
                'min:0.0001',
            ],
            'items.*.unit' => [
                'nullable',
                'string',
                'max:50',
            ],
            'items.*.unit_price' => [
                'required_with:items',
                'numeric',
                'min:0',
            ],
            'items.*.planned_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.realized_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items.*.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
