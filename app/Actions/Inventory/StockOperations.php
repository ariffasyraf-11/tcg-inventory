<?php

namespace App\Actions\Inventory;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOperations
{
    public function receive(User $actor, ProductVariant $variant, int $quantity, string $unitCost): void
    {
        if ($quantity < 1 || ! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $unitCost)) {
            throw ValidationException::withMessages(['quantity' => 'Positive quantity and valid unit cost required.']);
        }

        $workspaceId = $this->authorize($actor, $variant);

        DB::transaction(function () use ($actor, $variant, $quantity, $unitCost, $workspaceId) {
            $item = DB::table('inventory_items')->where('product_variant_id', $variant->id)->lockForUpdate()->first();

            if (! $item) {
                $id = DB::table('inventory_items')->insertGetId([
                    'workspace_id' => $workspaceId, 'product_variant_id' => $variant->id,
                    'quantity_on_hand' => 0, 'minimum_stock_level' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $item = DB::table('inventory_items')->where('id', $id)->lockForUpdate()->first();
            }

            $this->reconcile($item);

            if ($quantity > 4294967295 - $item->quantity_on_hand) {
                throw ValidationException::withMessages(['quantity' => 'Quantity limit exceeded.']);
            }

            $batchId = DB::table('inventory_batches')->insertGetId([
                'inventory_item_id' => $item->id, 'quantity_received' => $quantity,
                'quantity_remaining' => $quantity, 'unit_cost' => $unitCost,
                'received_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('inventory_items')->where('id', $item->id)->increment('quantity_on_hand', $quantity);
            $this->movement($item->id, $batchId, $actor->id, 'receipt', $quantity, 'Stock receipt');
            $this->reconcile(DB::table('inventory_items')->find($item->id));
        }, 3);
    }

    public function adjustOut(User $actor, ProductVariant $variant, int $quantity, string $reason): void
    {
        if ($quantity < 1 || trim($reason) === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['quantity' => 'Positive quantity and reason (up to 500 characters) required.']);
        }

        $workspaceId = $this->authorize($actor, $variant);

        DB::transaction(function () use ($actor, $variant, $quantity, $reason, $workspaceId) {
            $item = DB::table('inventory_items')->where('workspace_id', $workspaceId)
                ->where('product_variant_id', $variant->id)->lockForUpdate()->first();

            if (! $item || $quantity > $item->quantity_on_hand) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock.']);
            }

            $this->reconcile($item);
            $remaining = $quantity;
            $batches = DB::table('inventory_batches')->where('inventory_item_id', $item->id)
                ->where('quantity_remaining', '>', 0)->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();

            foreach ($batches as $batch) {
                if ($remaining === 0) {
                    break;
                }

                $take = min($remaining, $batch->quantity_remaining);
                DB::table('inventory_batches')->where('id', $batch->id)->decrement('quantity_remaining', $take);
                $this->movement($item->id, $batch->id, $actor->id, 'adjustment', -$take, $reason);
                $remaining -= $take;
            }

            if ($remaining !== 0) {
                throw ValidationException::withMessages(['quantity' => 'Batch reconciliation failed.']);
            }

            DB::table('inventory_items')->where('id', $item->id)->decrement('quantity_on_hand', $quantity);
            $this->reconcile(DB::table('inventory_items')->find($item->id));
        }, 3);
    }

    private function authorize(User $actor, ProductVariant $variant): int
    {
        $workspace = DB::table('workspaces')->where('owner_user_id', $actor->id)->first();

        if (! $workspace || $workspace->is_demo || ! DB::table('products')
            ->where('id', $variant->product_id)->where('workspace_id', $workspace->id)->exists()) {
            abort(403);
        }

        return $workspace->id;
    }

    private function reconcile(object $item): void
    {
        $sum = DB::table('inventory_batches')->where('inventory_item_id', $item->id)->sum('quantity_remaining');

        if ((int) $sum !== (int) $item->quantity_on_hand) {
            throw ValidationException::withMessages(['quantity' => 'Inventory balance is inconsistent.']);
        }
    }

    private function movement(int $itemId, int $batchId, int $actorId, string $type, int $signed, string $reason): void
    {
        DB::table('stock_movements')->insert([
            'inventory_item_id' => $itemId, 'inventory_batch_id' => $batchId,
            'actor_user_id' => $actorId, 'type' => $type,
            'signed_quantity' => $signed, 'reason' => $reason,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
