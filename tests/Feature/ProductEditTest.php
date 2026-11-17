<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Support\WorkspaceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_product_name_and_visibility_but_not_type(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Old', 'type' => 'single']);

        $this->actingAs($owner)->get('/catalog/'.$product->id.'/edit')->assertOk();
        $this->patch('/catalog/'.$product->id, ['name' => 'New', 'active' => 0, 'type' => 'sealed'])
            ->assertRedirect('/catalog');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New', 'active' => 0, 'type' => 'single']);
    }

    public function test_cross_workspace_product_edit_is_not_found(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Private Card', 'type' => 'single']);

        $this->actingAs($other)->get('/catalog/'.$product->id.'/edit')->assertNotFound();
        $this->patch('/catalog/'.$product->id, ['name' => 'Tampered', 'active' => 0])->assertNotFound();
    }

    public function test_workspace_edit_setting_controls_member_roles(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $operator = User::factory()->create();
        $viewer = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        foreach (['manager' => $manager, 'inventory_operator' => $operator, 'viewer' => $viewer] as $role => $user) {
            WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => $role]);
        }

        $access = app(WorkspaceAccess::class);
        $this->assertTrue($access->allows($manager, $workspace, 'catalog_edit'));
        $this->assertFalse($access->allows($operator, $workspace, 'catalog_edit'));
        $this->actingAs($owner)->patch('/settings/catalog-edit-role', ['catalog_edit_role' => 'inventory_operator'])->assertRedirect('/settings');
        $workspace->refresh();
        $this->assertTrue($access->allows($operator, $workspace, 'catalog_edit'));
        $this->assertFalse($access->allows($viewer, $workspace, 'catalog_edit'));
        $this->patch('/settings/catalog-edit-role', ['catalog_edit_role' => 'viewer'])->assertSessionHasErrors('catalog_edit_role');
    }
}
