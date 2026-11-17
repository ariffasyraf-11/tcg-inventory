<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceD4RegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_workspace_navigation_hides_owner_scoped_actions(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shared Shop']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => 'viewer']);
        $this->actingAs($viewer)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->get('/catalog')->assertOk()->assertSee('Shared Shop')->assertDontSee('>Invite staff<', false)->assertDontSee('>Sales<', false);
    }

    public function test_archived_product_cannot_be_selected_for_new_stock_receipt(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Archived Card', 'type' => 'single', 'active' => false]);
        $product->variants()->create(['sku' => 'ARCHIVED-001']);
        $this->actingAs($owner)->get('/inventory')->assertOk()->assertDontSee('ARCHIVED-001');
    }

    public function test_revoked_membership_cannot_modify_catalog_or_stock(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'CARD-001']);
        $membership = WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'manager']);
        $this->actingAs($member)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $membership->delete();
        $this->patch('/catalog/'.$product->id.'/sku', ['variant_id' => $variant->id, 'sku' => 'NO'])->assertNotFound();
        $this->post('/inventory/receive', ['variant_id' => $variant->id, 'quantity' => 1, 'unit_cost' => '1.00'])->assertForbidden();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'CARD-001']);
    }
}
