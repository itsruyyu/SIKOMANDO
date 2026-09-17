<?php

namespace Database\Factories;

use App\Enums\LpjItemStatus;
use App\Models\LpjItem;
use App\Models\LpjSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LpjItem>
 */
class LpjItemFactory extends Factory
{
    protected $model = LpjItem::class;

    public function definition(): array
    {
        $quantity = 10;
        $unitPrice = 1000000;
        $planned = $quantity * $unitPrice;
        $realized = $planned;

        return [
            'lpj_submission_id' => LpjSubmission::factory(),
            'proposal_budget_item_id' => null,
            'category' => 'Pengadaan Sarana',
            'budget_item' => 'Bibit Tanaman Unggul',
            'item_name' => 'Bibit Tanaman Unggul',
            'description' => 'Pengadaan bibit unggul bersertifikat untuk kelompok tani',
            'quantity' => $quantity,
            'unit' => 'paket',
            'unit_price' => $unitPrice,
            'subtotal' => $realized,
            'planned_amount' => $planned,
            'realized_amount' => $realized,
            'variance' => 0,
            'status' => LpjItemStatus::REPORTED,
            'notes' => fake()->sentence(),
        ];
    }
}
