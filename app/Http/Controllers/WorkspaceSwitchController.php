<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Support\WorkspaceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSwitchController extends Controller
{
    public function index(Request $request): View
    {
        $owned = Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );

        $memberships = Workspace::query()
            ->whereHas('memberships', fn ($query) => $query->where('user_id', $request->user()->id))
            ->where('is_demo', false)->orderBy('name')->get();

        return view('workspaces.switch', ['workspaces' => $memberships->prepend($owned)]);
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'view'), 404);
        $request->session()->put('selected_workspace_id', $workspace->id);

        return to_route('catalog.index')->with('status', 'Workspace selected.');
    }
}
