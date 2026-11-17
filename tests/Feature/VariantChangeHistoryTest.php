<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VariantChangeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_editor_can_view_sku_and_attribute_history_only_for_own_product(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $variant = $product->variants()->create(['sku' => 'BEFORE', 'condition' => 'Near Mint']);
        $other = Product::create(['workspace_id' => $workspace->id, 'name' => 'Other', 'type' => 'single']);
        $otherVariant = $other->variants()->create(['sku' => 'OTHER']);

        $this->actingAs($owner)->patch(route('catalog.sku.update', $product), ['variant_id' => $variant->id, 'sku' => 'AFTER'])->assertRedirect();
        $this->patch(route('catalog.variant.update', $product), ['variant_id' => $variant->id, 'condition' => 'Lightly Played'])->assertRedirect();
        DB::table('product_variant_attribute_changes')->insert([
            'product_variant_id' => $otherVariant->id, 'actor_user_id' => $owner->id,
            'attribute' => 'language', 'old_value' => 'English', 'new_value' => 'Japanese',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('catalog.edit', $product))->assertOk()
            ->assertSee('Variant change history')
            ->assertSee('BEFORE')->assertSee('AFTER')
            ->assertSee('Near Mint')->assertSee('Lightly Played')
            ->assertSee($owner->name)
            ->assertDontSee('Japanese');
    }

    public function test_other_workspace_owner_cannot_view_product_history(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);
        $this->actingAs($stranger)->get(route('catalog.edit', $product))->assertNotFound();
    }
}
