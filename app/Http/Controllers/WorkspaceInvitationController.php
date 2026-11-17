<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceInvitationController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->ownedWorkspace($request);

        return view('workspaces.invitations', [
            'workspace' => $workspace,
            'invitations' => WorkspaceInvitation::where('workspace_id', $workspace->id)->latest()->get(),
        ]);
    }

    public function inbox(Request $request): View
    {
        $email = Str::lower(trim($request->user()->email));

        return view('workspaces.invitation-inbox', [
            'invitations' => WorkspaceInvitation::query()
                ->where('email', $email)
                ->with('workspace:id,name')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function acceptFromInbox(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        DB::transaction(function () use ($request, $invitation): void {
            $invitation = WorkspaceInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($invitation->email, Str::lower(trim($request->user()->email))), 404);
            abort_if($invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast(), 404);
            abort_if($invitation->workspace->is_demo || $invitation->workspace->owner_user_id === $request->user()->id, 403);

            WorkspaceMembership::updateOrCreate(
                ['workspace_id' => $invitation->workspace_id, 'user_id' => $request->user()->id],
                ['role' => $invitation->role]
            );
            $invitation->update(['accepted_at' => now()]);
        });

        return to_route('workspace-invitations.inbox')->with('status', 'Invitation accepted. Workspace switching will be available in a later update.');
    }

    public function declineFromInbox(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        DB::transaction(function () use ($request, $invitation): void {
            $invitation = WorkspaceInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($invitation->email, Str::lower(trim($request->user()->email))), 404);
            abort_if($invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast(), 404);
            $invitation->update(['revoked_at' => now()]);
        });

        return to_route('workspace-invitations.inbox')->with('status', 'Invitation declined.');
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $this->ownedWorkspace($request);
        abort_if($workspace->is_demo, 403);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(WorkspaceMembership::ROLES)],
        ]);
        $email = Str::lower(trim($data['email']));
        abort_if($email === Str::lower($request->user()->email), 422);
        $token = Str::random(64);

        DB::transaction(function () use ($workspace, $request, $data, $email, $token): void {
            WorkspaceInvitation::where('workspace_id', $workspace->id)
                ->where('email', $email)->whereNull('accepted_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
            WorkspaceInvitation::create([
                'workspace_id' => $workspace->id,
                'invited_by_user_id' => $request->user()->id,
                'email' => $email,
                'role' => $data['role'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });

        return to_route('workspace-invitations.index')->with('invitation_link', route('workspace-invitations.show', $token));
    }

    public function show(Request $request, string $token): View
    {
        $invitation = $this->activeInvitation($token);
        abort_unless(hash_equals($invitation->email, Str::lower($request->user()->email)), 403);

        return view('workspaces.accept-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        DB::transaction(function () use ($request, $token): void {
            $invitation = WorkspaceInvitation::where('token_hash', hash('sha256', $token))
                ->lockForUpdate()->firstOrFail();
            abort_if($invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast(), 404);
            abort_unless(hash_equals($invitation->email, Str::lower($request->user()->email)), 403);
            abort_if($invitation->workspace->is_demo, 403);
            abort_if($invitation->workspace->owner_user_id === $request->user()->id, 403);

            WorkspaceMembership::updateOrCreate(
                ['workspace_id' => $invitation->workspace_id, 'user_id' => $request->user()->id],
                ['role' => $invitation->role]
            );
            $invitation->update(['accepted_at' => now()]);
        });

        return to_route('dashboard.index')->with('status', 'Invitation accepted. Workspace switching will be available in a later update.');
    }

    public function destroy(Request $request, WorkspaceInvitation $invitation): RedirectResponse
    {
        $workspace = $this->ownedWorkspace($request);
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $invitation->update(['revoked_at' => now()]);

        return to_route('workspace-invitations.index')->with('status', 'Invitation revoked.');
    }

    private function ownedWorkspace(Request $request): Workspace
    {
        return Workspace::firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }

    private function activeInvitation(string $token): WorkspaceInvitation
    {
        return WorkspaceInvitation::where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')->whereNull('revoked_at')
            ->where('expires_at', '>', now())->firstOrFail();
    }
}
