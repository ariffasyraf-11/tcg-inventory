<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Support\WorkspaceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkspaceMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_member_permissions_are_explicit(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $viewer = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $manager->id, 'role' => 'manager']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $access = app(WorkspaceAccess::class);
        $this->assertTrue($access->allows($owner, $workspace, 'manage'));
        $this->assertTrue($access->allows($manager, $workspace, 'stock'));
        $this->assertFalse($access->allows($manager, $workspace, 'manage'));
        $this->assertTrue($access->allows($viewer, $workspace, 'view'));
        $this->assertFalse($access->allows($viewer, $workspace, 'stock'));
        $this->assertFalse($access->allows($outsider, $workspace, 'view'));
    }

    public function test_membership_does_not_yet_grant_access_to_owner_only_routes(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);
        WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => 'manager']);

        $this->actingAs($member)->get('/settings')->assertOk()->assertDontSee('Private');
        $this->assertDatabaseCount('workspace_memberships', 1);
    }

    public function test_owner_cannot_be_added_as_member_and_roles_are_validated(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);

        try {
            WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'viewer']);
            $this->fail('Owner membership should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user_id', $exception->errors());
        }

        try {
            WorkspaceMembership::create(['workspace_id' => $workspace->id, 'user_id' => User::factory()->create()->id, 'role' => 'owner']);
            $this->fail('Owner role should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }
    }
}
