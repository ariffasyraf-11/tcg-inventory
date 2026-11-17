<?php

namespace App\Support;

use App\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceContext
{
    public function current(Request $request): Workspace
    {
        $selectedId = $request->session()->get('selected_workspace_id');

        if ($selectedId !== null) {
            $selected = Workspace::query()->find($selectedId);
            if ($selected && app(WorkspaceAccess::class)->allows($request->user(), $selected, 'view')) {
                return $selected;
            }

            $request->session()->forget('selected_workspace_id');
        }

        return Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }
}
