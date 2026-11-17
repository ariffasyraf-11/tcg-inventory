<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkuPermissionsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.sku-permissions', ['workspace' => $this->ownedWorkspace($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['single_sku_enabled' => ['required', 'boolean']]);
        $workspace = $this->ownedWorkspace($request);
        abort_if($workspace->is_demo, 403);
        $workspace->update(['single_sku_enabled' => (bool) $data['single_sku_enabled']]);

        return to_route('settings.sku-permissions')->with('status', 'SKU requirement updated.');
    }

    private function ownedWorkspace(Request $request): Workspace
    {
        return Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }
}
