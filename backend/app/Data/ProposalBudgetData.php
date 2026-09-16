<?php

namespace App\Data;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class ProposalBudgetData
{
    public function __construct(
        public readonly string $category,
        public readonly string $itemName,
        public readonly ?string $description,
        public readonly float $quantity,
        public readonly string $unit,
        public readonly float $unitPrice,
        public readonly int $sortOrder = 0,
    ) {
        if ($this->quantity <= 0) {
            throw new InvalidArgumentException(
                'Quantity harus lebih besar dari nol.'
            );
        }

        if ($this->unitPrice < 0) {
            throw new InvalidArgumentException(
                'Harga satuan tidak boleh negatif.'
            );
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            category: (string) Arr::get($data, 'category'),
            itemName: (string) Arr::get($data, 'item_name'),
            description: Arr::get($data, 'description'),
            quantity: (float) Arr::get($data, 'quantity'),
            unit: (string) Arr::get($data, 'unit'),
            unitPrice: (float) Arr::get($data, 'unit_price'),
            sortOrder: (int) Arr::get($data, 'sort_order', 0),
        );
    }

    public function subtotal(): float
    {
        return round($this->quantity * $this->unitPrice, 2);
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'item_name' => $this->itemName,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_price' => $this->unitPrice,
            'subtotal' => $this->subtotal(),
            'sort_order' => $this->sortOrder,
        ];
    }
}
