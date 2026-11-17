<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_detail_is_private_to_workspace_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Rare Dragon', 'type' => 'single']);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => null]);
        $item = InventoryItem::create(['workspace_id' => $workspace->id, 'product_variant_id' => $variant->id]);

        $this->actingAs($owner)->get('/inventory/'.$item->id)->assertOk()->assertSee('Rare Dragon');
        $this->get('/inventory?q=Dragon')->assertOk()->assertSee('Rare Dragon');
        $this->get('/inventory?q=Missing')->assertOk()->assertDontSee('Rare Dragon');

        $this->actingAs($other)->get('/inventory/'.$item->id)->assertNotFound();
        $this->get('/inventory?q=Dragon')->assertOk()->assertDontSee('Rare Dragon');
    }
}
