<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_select_workspace_and_read_catalog_without_mutating_it(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shared Shop']);
        Product::create(['workspace_id' => $workspace->id, 'name' => 'Shared Card', 'type' => 'single']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'viewer']);

        $this->actingAs($member)->get('/workspaces/switch')->assertOk()->assertSee('Shared Shop');
        $this->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $this->get('/catalog')->assertOk()->assertSee('Shared Card');
        $this->post('/catalog', ['name' => 'Unauthorized', 'type' => 'single'])->assertForbidden();
        $this->post('/inventory/receive', [])->assertForbidden();
    }

    public function test_unrelated_workspace_cannot_be_selected(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Hidden Shop']);

        $this->actingAs($outsider)->post('/workspaces/switch/'.$workspace->id)->assertNotFound();
        $this->get('/catalog')->assertDontSee('Hidden Shop');
    }

    public function test_revoked_membership_invalidates_selected_workspace(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Revoked Shop']);
        $membership = WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'viewer']);

        $this->actingAs($member)->post('/workspaces/switch/'.$workspace->id)->assertRedirect('/catalog');
        $membership->delete();
        $this->get('/catalog')->assertOk()->assertDontSee('Revoked Shop');
    }
}
