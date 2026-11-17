<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_sku_with_audit_and_archive_product(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'OLD-001']);

        $this->actingAs($owner)->patch('/catalog/'.$product->id.'/sku', ['variant_id' => $variant->id, 'sku' => 'NEW-001'])->assertRedirect();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'NEW-001']);
        $this->assertDatabaseHas('product_sku_changes', ['product_variant_id' => $variant->id, 'actor_user_id' => $owner->id, 'old_sku' => 'OLD-001', 'new_sku' => 'NEW-001']);
        $this->delete('/catalog/'.$product->id)->assertRedirect('/catalog');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => 0]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
    }

    public function test_duplicate_sku_is_rejected_within_workspace(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $first = Product::create(['workspace_id' => $workspace->id, 'name' => 'First', 'type' => 'single']);
        $second = Product::create(['workspace_id' => $workspace->id, 'name' => 'Second', 'type' => 'single']);
        $first->variants()->create(['sku' => 'TAKEN']);
        $variant = $second->variants()->create(['sku' => 'OTHER']);

        $this->actingAs($owner)->patch('/catalog/'.$second->id.'/sku', ['variant_id' => $variant->id, 'sku' => 'TAKEN'])->assertSessionHasErrors('sku');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'OTHER']);
    }

    public function test_viewer_and_other_workspace_cannot_change_sku_or_archive(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'KEEP']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($viewer)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->patch('/catalog/'.$product->id.'/sku', ['variant_id' => $variant->id, 'sku' => 'NO'])->assertForbidden();
        $this->delete('/catalog/'.$product->id)->assertForbidden();
        $this->actingAs($outsider)->patch('/catalog/'.$product->id.'/sku', ['variant_id' => $variant->id, 'sku' => 'NO'])->assertNotFound();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => 1]);
    }
}
