<?php

namespace App\Actions\Sales;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSale
{
    public function execute(User $actor, ProductVariant $variant, int $quantity, string $unitPrice, ?string $reference = null): int
    {
        if ($quantity < 1 || ! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $unitPrice)) {
            throw ValidationException::withMessages(['quantity' => 'Positive quantity and valid sale price required.']);
        }

        $workspace = DB::table('workspaces')->where('owner_user_id', $actor->id)->first();

        if (! $workspace || $workspace->is_demo || ! DB::table('products')
            ->where('id', $variant->product_id)->where('workspace_id', $workspace->id)->exists()) {
            abort(403);
        }

        return DB::transaction(function () use ($actor, $variant, $quantity, $unitPrice, $reference, $workspace) {
            $item = DB::table('inventory_items')->where('workspace_id', $workspace->id)
                ->where('product_variant_id', $variant->id)->lockForUpdate()->first();

            if (! $item || $item->quantity_on_hand < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Insufficient stock for sale.']);
            }

            $batches = DB::table('inventory_batches')->where('inventory_item_id', $item->id)
                ->where('quantity_remaining', '>', 0)->orderBy('received_at')->orderBy('id')
                ->lockForUpdate()->get();

            if ((int) $batches->sum('quantity_remaining') !== (int) $item->quantity_on_hand) {
                throw ValidationException::withMessages(['quantity' => 'Inventory balance is inconsistent.']);
            }

            $remaining = $quantity;
            $costCents = 0;
            $allocations = [];

            foreach ($batches as $batch) {
                if ($remaining === 0) {
                    break;
                }

                $take = min($remaining, $batch->quantity_remaining);
                $cents = (int) round(((float) $batch->unit_cost) * 100);
                $costCents += $take * $cents;
                $allocations[] = ['batch' => $batch, 'quantity' => $take, 'unit_cost' => $batch->unit_cost, 'cost_cents' => $take * $cents];
                $remaining -= $take;
            }

            if ($remaining !== 0) {
                throw ValidationException::withMessages(['quantity' => 'Not enough batch stock.']);
            }

            $revenueCents = $quantity * (int) round(((float) $unitPrice) * 100);
            $cost = number_format($costCents / 100, 2, '.', '');
            $revenue = number_format($revenueCents / 100, 2, '.', '');
            $now = now();

            $saleId = DB::table('sales')->insertGetId([
                'workspace_id' => $workspace->id, 'actor_user_id' => $actor->id,
                'reference' => $reference, 'total_revenue' => $revenue, 'total_cost' => $cost,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $lineId = DB::table('sale_lines')->insertGetId([
                'sale_id' => $saleId, 'product_variant_id' => $variant->id, 'quantity' => $quantity,
                'unit_price' => $unitPrice, 'line_revenue' => $revenue, 'line_cost' => $cost,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($allocations as $allocation) {
                $batch = $allocation['batch'];
                DB::table('inventory_batches')->where('id', $batch->id)->decrement('quantity_remaining', $allocation['quantity']);
                DB::table('sale_batch_allocations')->insert([
                    'sale_line_id' => $lineId, 'inventory_batch_id' => $batch->id,
                    'quantity' => $allocation['quantity'], 'unit_cost' => $allocation['unit_cost'],
                    'total_cost' => number_format($allocation['cost_cents'] / 100, 2, '.', ''),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('stock_movements')->insert([
                    'inventory_item_id' => $item->id, 'inventory_batch_id' => $batch->id,
                    'actor_user_id' => $actor->id, 'type' => 'sale',
                    'signed_quantity' => -$allocation['quantity'],
                    'reason' => 'Sale #'.$saleId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            DB::table('inventory_items')->where('id', $item->id)->decrement('quantity_on_hand', $quantity);

            return $saleId;
        }, 3);
    }
}
