<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_catalog(): void
    {
        $this->get('/catalog')->assertRedirect('/login');
        $this->post('/catalog', ['name' => 'Sample'])->assertRedirect('/login');
    }

    public function test_owner_can_create_a_single_and_a_sealed_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/catalog', [
            'name' => 'Sample Dragon', 'type' => 'single', 'sku' => 'DRAGON-NM',
            'condition' => 'Near Mint', 'language' => 'English', 'finish' => 'Regular',
        ])->assertRedirect('/catalog');

        $this->actingAs($user)->post('/catalog', [
            'name' => 'Starter Deck A', 'type' => 'sealed', 'sku' => 'DECK-A',
            'sealed_format' => 'Starter deck',
        ])->assertRedirect('/catalog');

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 2);
        $this->assertSame(1, Workspace::where('owner_user_id', $user->id)->count());
    }

    public function test_products_are_private_to_their_owner(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post('/catalog', [
            'name' => 'Private Card', 'type' => 'single', 'sku' => 'PRIVATE',
        ])->assertRedirect('/catalog');

        $this->actingAs($second)->get('/catalog')
            ->assertOk()
            ->assertDontSee('Private Card');

        $this->actingAs($second)->post('/catalog', [
            'name' => 'Another Card', 'type' => 'single', 'sku' => 'PRIVATE',
        ])->assertRedirect('/catalog');

        $this->assertSame(2, Product::count());
    }

    public function test_duplicate_sku_within_workspace_is_rejected(): void
    {
        $user = User::factory()->create();
        $payload = ['name' => 'Card', 'type' => 'single', 'sku' => 'SAME'];

        $this->actingAs($user)->post('/catalog', $payload)->assertRedirect('/catalog');
        $this->actingAs($user)->from('/catalog')->post('/catalog', $payload)->assertSessionHasErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_single_sku_can_be_disabled_without_affecting_sealed_products(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'My shop']);

        $this->actingAs($user)->post('/catalog', ['name' => 'Card', 'type' => 'single'])
            ->assertSessionHasErrors('sku');

        $this->patch('/settings/single-sku', ['single_sku_enabled' => '0'])->assertRedirect('/settings');
        $this->post('/catalog', ['name' => 'Card A', 'type' => 'single'])->assertRedirect('/catalog');
        $this->post('/catalog', ['name' => 'Card B', 'type' => 'single'])->assertRedirect('/catalog');
        $this->assertSame(2, Product::where('workspace_id', $workspace->id)->count());
        $this->assertSame(2, ProductVariant::whereNull('sku')->count());

        $this->post('/catalog', ['name' => 'Sealed', 'type' => 'sealed'])->assertSessionHasErrors('sku');
        $this->patch('/settings/single-sku', ['single_sku_enabled' => '1'])->assertRedirect('/settings');
        $this->post('/catalog', ['name' => 'Card C', 'type' => 'single'])->assertSessionHasErrors('sku');
        $this->assertSame(2, ProductVariant::whereNull('sku')->count());
    }

    public function test_demo_workspace_cannot_be_modified(): void
    {
        $user = User::factory()->create();
        Workspace::create(['owner_user_id' => $user->id, 'name' => 'Demo', 'is_demo' => true]);

        $this->actingAs($user)->post('/catalog', [
            'name' => 'Card', 'type' => 'single', 'sku' => 'TEST',
        ])->assertForbidden();
    }
}
