<?php

namespace Tests\Feature;

use App\Actions\Inventory\StockOperations;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'Seller']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Test Card', 'type' => 'single']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CARD-1']);

        return [$user, $variant];
    }

    public function test_receipts_and_fifo_adjustments_reconcile(): void
    {
        [$user, $variant] = $this->fixture();
        $stock = app(StockOperations::class);
        $stock->receive($user, $variant, 5, '2.50');
        $stock->receive($user, $variant, 4, '3.00');
        $stock->adjustOut($user, $variant, 6, 'Damaged');

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity_on_hand' => 3]);
        $this->assertDatabaseHas('inventory_batches', ['unit_cost' => '2.50', 'quantity_remaining' => 0]);
        $this->assertDatabaseHas('inventory_batches', ['unit_cost' => '3.00', 'quantity_remaining' => 3]);
        $this->assertDatabaseCount('stock_movements', 4);
        $this->assertDatabaseHas('stock_movements', ['actor_user_id' => $user->id, 'signed_quantity' => -5, 'reason' => 'Damaged']);
    }

    public function test_insufficient_stock_does_not_change_balances(): void
    {
        [$user, $variant] = $this->fixture();
        $stock = app(StockOperations::class);
        $stock->receive($user, $variant, 2, '1.00');

        try {
            $stock->adjustOut($user, $variant, 3, 'Loss');
            $this->fail('Expected insufficient stock rejection.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity_on_hand' => 2]);
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_other_owner_cannot_modify_stock(): void
    {
        [$user, $variant] = $this->fixture();
        $other = User::factory()->create();
        Workspace::create(['owner_user_id' => $other->id, 'name' => 'Other']);

        $this->expectException(HttpException::class);
        app(StockOperations::class)->receive($other, $variant, 1, '1.00');
    }

    public function test_adjustment_requires_reason(): void
    {
        [$user, $variant] = $this->fixture();
        $stock = app(StockOperations::class);
        $stock->receive($user, $variant, 2, '1.00');

        $this->expectException(ValidationException::class);
        $stock->adjustOut($user, $variant, 1, '');
    }
}
