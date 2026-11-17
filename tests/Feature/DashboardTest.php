<?php

namespace Tests\Feature;

use App\Actions\Inventory\StockOperations;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_private_and_threshold_update_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Seller']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'CARD-1']);
        app(StockOperations::class)->receive($owner, $variant, 3, '2.00');
        $itemId = DB::table('inventory_items')->where('product_variant_id', $variant->id)->value('id');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($other)->get('/dashboard')->assertOk()->assertDontSee('CARD-1');
        $this->patch('/dashboard/threshold', ['inventory_item_id' => $itemId, 'minimum_stock_level' => 5])->assertForbidden();

        $this->actingAs($owner)->patch('/dashboard/threshold', ['inventory_item_id' => $itemId, 'minimum_stock_level' => 5])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Low stock');
        $this->assertDatabaseHas('inventory_items', ['id' => $itemId, 'minimum_stock_level' => 5]);
    }
}
