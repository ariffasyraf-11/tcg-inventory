<?php

namespace Tests\Feature;

use App\Actions\Inventory\StockOperations;
use App\Actions\Sales\RecordSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_consumes_oldest_batches_and_persists_cost(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'Seller']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Test card', 'type' => 'single', 'active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'TEST-1']);
        $stock = app(StockOperations::class);
        $stock->receive($user, $variant, 2, '3.00');
        $stock->receive($user, $variant, 3, '5.00');

        $saleId = app(RecordSale::class)->execute($user, $variant, 4, '10.00', 'ORDER-1');

        $this->assertDatabaseHas('sales', ['id' => $saleId, 'total_revenue' => '40', 'total_cost' => '16']);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity_on_hand' => 1]);
        $this->assertDatabaseCount('sale_batch_allocations', 2);
        $this->assertDatabaseCount('stock_movements', 4);
    }

    public function test_sale_rejects_overselling_without_writing_sale(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'Seller']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CARD-1']);
        app(StockOperations::class)->receive($user, $variant, 1, '2.00');

        try {
            app(RecordSale::class)->execute($user, $variant, 2, '10.00');
            $this->fail('Expected stock validation error.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity_on_hand' => 1]);
        }
    }

    public function test_sale_rejects_another_owner_variant(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Private card', 'type' => 'single']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'PRIVATE']);

        $this->actingAs($other)->post('/sales', [
            'variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => '10.00',
        ])->assertForbidden();
    }
}
