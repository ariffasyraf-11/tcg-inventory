<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMemberStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_receive_stock_in_selected_workspace_with_actor_attribution(): void
    {
        $owner = User::factory()->create();
        $operator = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shared Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'OP-001']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $operator->id, 'role' => 'inventory_operator']);

        $this->actingAs($operator)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->post('/inventory/receive', ['variant_id' => $variant->id, 'quantity' => 3, 'unit_cost' => '2.50'])->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['workspace_id' => $workspace->id, 'product_variant_id' => $variant->id, 'quantity_on_hand' => 3]);
        $this->assertDatabaseHas('stock_movements', ['actor_user_id' => $operator->id, 'signed_quantity' => 3]);
        $this->post('/inventory/adjust', ['variant_id' => $variant->id, 'quantity' => 1, 'reason' => 'Damaged'])->assertRedirect('/inventory');
        $this->assertDatabaseHas('inventory_items', ['workspace_id' => $workspace->id, 'product_variant_id' => $variant->id, 'quantity_on_hand' => 2]);
    }

    public function test_viewer_cannot_mutate_and_operator_cannot_mutate_other_workspace(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $operator = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shared']);
        $otherWorkspace = Workspace::create(['owner_user_id' => User::factory()->create()->id, 'name' => 'Private']);
        $variant = Product::create(['workspace_id' => $otherWorkspace->id, 'name' => 'Private', 'type' => 'single'])->variants()->create(['sku' => 'PRIVATE']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => 'viewer']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $operator->id, 'role' => 'inventory_operator']);

        $this->actingAs($viewer)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->post('/inventory/receive', ['variant_id' => $variant->id, 'quantity' => 1, 'unit_cost' => '1.00'])->assertForbidden();
        $this->actingAs($operator)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->post('/inventory/receive', ['variant_id' => $variant->id, 'quantity' => 1, 'unit_cost' => '1.00'])->assertForbidden();
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
