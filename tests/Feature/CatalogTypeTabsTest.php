<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTypeTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_filter_product_types_and_preserve_status(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        Product::create(['workspace_id' => $workspace->id, 'name' => 'Single A', 'type' => 'single', 'active' => true])->variants()->create(['sku' => 'S-1']);
        Product::create(['workspace_id' => $workspace->id, 'name' => 'Sealed B', 'type' => 'sealed', 'active' => true])->variants()->create(['sku' => 'B-1']);
        Product::create(['workspace_id' => $workspace->id, 'name' => 'Archived Single', 'type' => 'single', 'active' => false])->variants()->create(['sku' => 'S-2']);

        $this->actingAs($owner)->get('/catalog?type=single')->assertOk()
            ->assertSee('Singles &amp; Used', false)->assertSee('Single A')->assertDontSee('Sealed B')
            ->assertDontSee('Archived Single');
        $this->get('/catalog?type=sealed')->assertOk()->assertSee('Sealed B')->assertDontSee('Single A');
        $this->get('/catalog?type=single&status=archived')->assertOk()->assertSee('Archived Single')->assertDontSee('Sealed B');
        $this->get('/catalog?type=invalid')->assertNotFound();
    }

    public function test_single_with_sku_can_edit_it_but_sku_less_single_cannot(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $single = Product::create(['workspace_id' => $workspace->id, 'name' => 'Single', 'type' => 'single']);
        $single->variants()->create(['sku' => 'SINGLE-1']);
        $sealed = Product::create(['workspace_id' => $workspace->id, 'name' => 'Sealed', 'type' => 'sealed']);
        $sealed->variants()->create(['sku' => 'SEALED-1']);
        $withoutSku = Product::create(['workspace_id' => $workspace->id, 'name' => 'Without SKU', 'type' => 'single']);
        $withoutSkuVariant = $withoutSku->variants()->create(['sku' => null]);
        $this->actingAs($owner)->get(route('catalog.edit', $single))->assertOk()->assertSee('Update SKU');
        $this->get(route('catalog.edit', $sealed))->assertOk()->assertSee('Update SKU');
        $this->get(route('catalog.edit', $withoutSku))->assertOk()->assertSee('This single card has no SKU')->assertSee('Assign SKU');
        $this->patch(route('catalog.sku.update', $single), ['variant_id' => $single->variants->first()->id, 'sku' => 'SINGLE-2'])->assertRedirect(route('catalog.edit', $single));
        $this->assertDatabaseHas('product_variants', ['product_id' => $single->id, 'sku' => 'SINGLE-2']);
        $this->patch(route('catalog.sku.update', $single), ['variant_id' => $single->variants->first()->id, 'sku' => ''])->assertSessionHasErrors('sku');
        $this->patch(route('catalog.sku.update', $withoutSku), ['variant_id' => $withoutSkuVariant->id, 'sku' => 'NEW-SKU'])->assertRedirect(route('catalog.edit', $withoutSku));
        $this->assertDatabaseHas('product_variants', ['id' => $withoutSkuVariant->id, 'sku' => 'NEW-SKU']);
        $this->assertDatabaseHas('product_sku_changes', ['product_variant_id' => $withoutSkuVariant->id, 'old_sku' => null, 'new_sku' => 'NEW-SKU']);
    }
}
