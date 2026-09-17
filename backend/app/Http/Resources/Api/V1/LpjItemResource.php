<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LpjItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LpjItem
 */
class LpjItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lpj_submission_id' => $this->lpj_submission_id,
            'proposal_budget_item_id' => $this->proposal_budget_item_id,
            'category' => $this->category,
            'budget_item' => $this->budget_item,
            'item_name' => $this->item_name,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            'planned_amount' => (float) $this->planned_amount,
            'realized_amount' => (float) $this->realized_amount,
            'variance' => (float) $this->variance,
            'evidence_document_id' => $this->evidence_document_id,
            'evidence_path' => $this->evidence_path,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
