<?php

namespace Database\Factories;

use App\Enums\DisbursementTransactionStatus;
use App\Models\Disbursement;
use App\Models\DisbursementTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DisbursementTransaction>
 */
class DisbursementTransactionFactory extends Factory
{
    protected $model = DisbursementTransaction::class;

    public function definition(): array
    {
        return [
            'disbursement_id' => Disbursement::factory(),
            'recorded_by' => User::factory(),
            'transaction_number' => 'TXN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'transaction_type' => 'transfer',
            'amount' => 50000000,
            'status' => DisbursementTransactionStatus::CONFIRMED,
            'transaction_date' => now()->toDateString(),
            'bank_reference' => 'REF-'.Str::upper(Str::random(12)),
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Yayasan Peduli Sesama',
            'recipient_name' => 'Yayasan Peduli Sesama',
            'notes' => fake()->sentence(),
        ];
    }
}
