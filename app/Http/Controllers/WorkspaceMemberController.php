<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkspaceMemberController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspace($request);

        return view('workspaces.members', [
            'workspace' => $workspace,
            'members' => $workspace->memberships()->with('user:id,name,email')->latest()->paginate(15),
        ]);
    }

    public function update(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($membership->workspace_id === $workspace->id, 404);
        $data = $request->validate(['role' => ['required', Rule::in(WorkspaceMembership::ROLES)]]);
        $membership->update(['role' => $data['role']]);

        return to_route('workspace-members.index')->with('status', 'Member role updated.');
    }

    public function destroy(Request $request, WorkspaceMembership $membership): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($membership->workspace_id === $workspace->id, 404);
        $membership->delete();

        return to_route('workspace-members.index')->with('status', 'Member removed. Their previous stock movements remain in the audit history.');
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }
}
