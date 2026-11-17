<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_see_and_accept_invitation_from_inbox(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'staff@example.com']);
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Invitation Test Shop']);
        $this->actingAs($owner)->post('/workspace/invitations', ['email' => 'staff@example.com', 'role' => 'viewer']);
        $invitation = WorkspaceInvitation::firstOrFail();

        $this->actingAs($recipient)->get('/workspace/invitations/inbox')->assertOk()->assertSee('Invitation Test Shop');
        $this->post('/workspace/invitations/inbox/'.$invitation->id.'/accept')->assertRedirect('/workspace/invitations/inbox');
        $this->assertDatabaseHas('workspace_memberships', ['workspace_id' => $workspace->id, 'user_id' => $recipient->id, 'role' => 'viewer']);
        $this->post('/workspace/invitations/inbox/'.$invitation->id.'/accept')->assertNotFound();
    }

    public function test_other_accounts_cannot_view_or_accept_invitation(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'staff@example.com']);
        $outsider = User::factory()->create();
        Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Hidden Invitation Shop']);
        $this->actingAs($owner)->post('/workspace/invitations', ['email' => $recipient->email, 'role' => 'viewer']);
        $invitation = WorkspaceInvitation::firstOrFail();

        $this->actingAs($outsider)->get('/workspace/invitations/inbox')->assertOk()->assertDontSee('Hidden Invitation Shop');
        $this->post('/workspace/invitations/inbox/'.$invitation->id.'/accept')->assertNotFound();
        $this->post('/workspace/invitations/inbox/'.$invitation->id.'/decline')->assertNotFound();
    }

    public function test_recipient_can_decline_invitation(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create(['email' => 'staff@example.com']);
        Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $this->actingAs($owner)->post('/workspace/invitations', ['email' => $recipient->email, 'role' => 'manager']);
        $invitation = WorkspaceInvitation::firstOrFail();

        $this->actingAs($recipient)->post('/workspace/invitations/inbox/'.$invitation->id.'/decline')->assertRedirect('/workspace/invitations/inbox');
        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->assertDatabaseCount('workspace_memberships', 0);
    }
}
