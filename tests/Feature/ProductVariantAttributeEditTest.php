<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantAttributeEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_edits_single_variant_attributes_with_audit(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'CARD-1', 'condition' => 'NM']);

        $this->actingAs($owner)->patch('/catalog/'.$product->id.'/variant', [
            'variant_id' => $variant->id, 'condition' => 'LP', 'language' => 'English',
            'finish' => 'Foil', 'card_number' => '001',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'CARD-1', 'condition' => 'LP', 'language' => 'English']);
        $this->assertDatabaseHas('product_variant_attribute_changes', [
            'product_variant_id' => $variant->id, 'actor_user_id' => $owner->id,
            'attribute' => 'condition', 'old_value' => 'NM', 'new_value' => 'LP',
        ]);
    }

    public function test_other_product_variant_and_viewer_cannot_edit(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $other = Product::create(['workspace_id' => $workspace->id, 'name' => 'Other', 'type' => 'single']);
        $variant = $other->variants()->create(['sku' => 'OTHER']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($owner)->patch('/catalog/'.$product->id.'/variant', ['variant_id' => $variant->id, 'condition' => 'NM'])->assertNotFound();
        $this->actingAs($viewer)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->patch('/catalog/'.$other->id.'/variant', ['variant_id' => $variant->id, 'condition' => 'NM'])->assertForbidden();
        $this->assertDatabaseMissing('product_variant_attribute_changes', ['product_variant_id' => $variant->id]);
    }

    public function test_sealed_product_edits_only_allowed_fields(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Box', 'type' => 'sealed']);
        $variant = $product->variants()->create(['sku' => 'BOX']);
        $this->actingAs($owner)->patch('/catalog/'.$product->id.'/variant', [
            'variant_id' => $variant->id, 'condition' => 'NM', 'language' => 'English', 'sealed_format' => 'Booster box',
        ])->assertRedirect();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'condition' => null, 'language' => 'English', 'sealed_format' => 'Booster box']);
    }
}
