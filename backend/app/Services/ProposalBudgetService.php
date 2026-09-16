<?php

namespace App\Services;

use App\Data\ProposalBudgetData;
use App\Models\Proposal;
use App\Models\ProposalBudgetItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalBudgetService
{
    public function replaceItems(
        Proposal $proposal,
        array $items,
        ?string $requestId = null,
    ): Proposal {
        if (! in_array($proposal->status->value, ['draft', 'revision'], true)) {
            throw ValidationException::withMessages([
                'status' => 'RAB hanya dapat diubah pada status draft atau revision.',
            ]);
        }

        $budgetData = array_map(
            fn (array $item): ProposalBudgetData => ProposalBudgetData::fromArray($item),
            $items
        );

        $total = collect($budgetData)->sum(
            fn (ProposalBudgetData $item): float => $item->subtotal()
        );

        $program = $proposal->grantProgram;

        if (
            $program->minimum_amount !== null
            && $total < (float) $program->minimum_amount
        ) {
            throw ValidationException::withMessages([
                'budget' => 'Total RAB berada di bawah batas minimum program.',
            ]);
        }

        if (
            $program->maximum_amount !== null
            && $total > (float) $program->maximum_amount
        ) {
            throw ValidationException::withMessages([
                'budget' => 'Total RAB melebihi batas maksimum program.',
            ]);
        }

        return DB::transaction(function () use (
            $proposal,
            $budgetData,
            $total,
            $requestId
        ): Proposal {
            $oldItems = $proposal->budgetItems()
                ->get()
                ->map(fn (ProposalBudgetItem $item): array => [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])
                ->all();

            $proposal->budgetItems()->delete();

            foreach ($budgetData as $item) {
                $proposal->budgetItems()->create($item->toArray());
            }

            $proposal->requested_amount = $total;
            $proposal->save();

            app(AuditLogService::class)->record(
                action: 'proposal.budget_replaced',
                module: 'proposal',
                entityType: Proposal::class,
                entityId: $proposal->id,
                oldValues: [
                    'budget_items' => $oldItems,
                ],
                newValues: [
                    'budget_items' => $proposal->budgetItems()
                        ->get()
                        ->toArray(),
                    'requested_amount' => $total,
                ],
                requestId: $requestId,
            );

            return $proposal->refresh();
        });
    }

    public function calculateTotal(Proposal $proposal): float
    {
        return (float) $proposal->budgetItems()
            ->get()
            ->sum(fn (ProposalBudgetItem $item): float => (float) $item->quantity * (float) $item->unit_price
            );
    }
}
