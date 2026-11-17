<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_inventory(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
        $this->post('/inventory/receive')->assertRedirect('/login');
    }

    public function test_owner_can_receive_and_adjust_stock_through_forms(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'Seller']);
        $product = Product::create(['workspace_id' => $workspace->id, 'type' => 'single', 'name' => 'Dragon']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'DRAGON']);

        $this->actingAs($user)->get('/inventory')->assertOk()->assertSee('Receive stock');
        $this->post('/inventory/receive', ['variant_id' => $variant->id, 'quantity' => 5, 'unit_cost' => '1.50'])
            ->assertRedirect('/inventory');
        $this->post('/inventory/adjust', ['variant_id' => $variant->id, 'quantity' => 2, 'reason' => 'Damage'])
            ->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity_on_hand' => 3]);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_other_owner_cannot_receive_stock_for_private_variant(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);
        $product = Product::create(['workspace_id' => $workspace->id, 'type' => 'single', 'name' => 'Private card']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'PRIVATE']);

        $this->actingAs($other)->post('/inventory/receive', [
            'variant_id' => $variant->id, 'quantity' => 2, 'unit_cost' => '1.00',
        ])->assertForbidden();
        $this->assertDatabaseCount('inventory_items', 0);
    }
}
