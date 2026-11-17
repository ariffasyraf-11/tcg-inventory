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

    public function test_single_edit_page_disables_sku_but_sealed_keeps_sku_update(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $single = Product::create(['workspace_id' => $workspace->id, 'name' => 'Single', 'type' => 'single']);
        $single->variants()->create(['sku' => 'SINGLE-1']);
        $sealed = Product::create(['workspace_id' => $workspace->id, 'name' => 'Sealed', 'type' => 'sealed']);
        $sealed->variants()->create(['sku' => 'SEALED-1']);
        $this->actingAs($owner)->get(route('catalog.edit', $single))->assertOk()
            ->assertSee('SKU editing is disabled for singles')->assertDontSee('Update SKU');
        $this->get(route('catalog.edit', $sealed))->assertOk()->assertSee('Update SKU');
    }
}
