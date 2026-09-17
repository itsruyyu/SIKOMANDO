<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RecordDisbursementTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'nullable',
                'numeric',
                'min:1',
            ],
            'transaction_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transaction_date' => [
                'nullable',
                'date',
            ],
            'bank_reference' => [
                'nullable',
                'string',
                'max:150',
            ],
            'recipient_name' => [
                'nullable',
                'string',
                'max:255',
            ],
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
            'amount.numeric' => 'Nominal transaksi pencairan harus berupa angka valid.',
            'amount.min' => 'Nominal transaksi pencairan minimal 1 rupiah.',
            'bank_reference.max' => 'Nomor referensi perbankan maksimal 150 karakter.',
        ];
    }
}
