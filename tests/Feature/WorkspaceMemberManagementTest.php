<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_change_role_and_remove_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $membership = WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'viewer']);

        $this->actingAs($owner)->get('/workspace/members')->assertOk()->assertSee($member->email);
        $this->patch('/workspace/members/'.$membership->id, ['role' => 'manager'])->assertRedirect('/workspace/members');
        $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id, 'role' => 'manager']);
        $this->delete('/workspace/members/'.$membership->id)->assertRedirect('/workspace/members');
        $this->assertDatabaseMissing('workspace_memberships', ['id' => $membership->id]);
    }

    public function test_member_cannot_manage_other_workspace_members(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $membership = WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'viewer']);

        $this->actingAs($other)->patch('/workspace/members/'.$membership->id, ['role' => 'manager'])->assertNotFound();
        $this->delete('/workspace/members/'.$membership->id)->assertNotFound();
        $this->assertDatabaseHas('workspace_memberships', ['id' => $membership->id, 'role' => 'viewer']);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $membership = WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => User::factory()->create()->id, 'role' => 'viewer']);
        $this->actingAs($owner)->patch('/workspace/members/'.$membership->id, ['role' => 'owner'])->assertSessionHasErrors('role');
    }
}
