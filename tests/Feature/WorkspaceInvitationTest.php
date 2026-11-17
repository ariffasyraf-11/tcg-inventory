<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_matching_account_can_accept_once(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'member@example.com']);
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);

        $response = $this->actingAs($owner)->post('/workspace/invitations', [
            'email' => 'MEMBER@example.com', 'role' => 'viewer',
        ])->assertRedirect('/workspace/invitations');

        $link = $response->getSession()->get('invitation_link');
        $this->assertNotNull($link);
        $invitation = WorkspaceInvitation::firstOrFail();
        $this->assertSame('member@example.com', $invitation->email);
        $this->assertStringNotContainsString($invitation->token_hash, $link);

        $this->actingAs($invitee)->get($link)->assertOk();
        $this->post($link)->assertRedirect('/dashboard');
        $this->assertDatabaseHas('workspace_memberships', [
            'workspace_id' => $workspace->id, 'user_id' => $invitee->id, 'role' => 'viewer',
        ]);
        $this->post($link)->assertNotFound();
    }

    public function test_wrong_account_and_revoked_or_expired_links_fail(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'member@example.com']);
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $response = $this->actingAs($owner)->post('/workspace/invitations', [
            'email' => $invitee->email, 'role' => 'manager',
        ]);
        $link = $response->getSession()->get('invitation_link');

        $this->actingAs($other)->post($link)->assertForbidden();
        $invitation = WorkspaceInvitation::firstOrFail();
        $this->actingAs($owner)->delete('/workspace/invitations/'.$invitation->id)->assertRedirect('/workspace/invitations');
        $this->actingAs($invitee)->post($link)->assertNotFound();
        $this->assertSame(0, WorkspaceMembership::count());
    }

    public function test_non_owner_cannot_manage_other_workspace_invitations(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $this->actingAs($other)->get('/workspace/invitations')->assertOk()->assertDontSee('Shop');
        $this->assertDatabaseCount('workspace_invitations', 0);
    }
}
