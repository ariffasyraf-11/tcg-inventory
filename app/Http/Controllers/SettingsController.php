<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.index', ['workspace' => $this->workspace($request)]);
    }

    public function profile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($data);

        return to_route('settings.index')->with('status', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();

        return to_route('settings.index')->with('status', 'Password updated.');
    }

    public function workspaceName(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace = $this->workspace($request);
        abort_if($workspace->is_demo, 403);
        $workspace->update($data);

        return to_route('settings.index')->with('status', 'Workspace updated.');
    }

    public function singleSku(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'single_sku_enabled' => ['required', 'boolean'],
        ]);

        $workspace = $this->workspace($request);
        abort_if($workspace->is_demo, 403);
        $workspace->update(['single_sku_enabled' => (bool) $data['single_sku_enabled']]);

        return to_route('settings.index')->with('status', 'Single-card SKU preference updated.');
    }

    public function catalogEditRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'catalog_edit_role' => ['required', Rule::in(['owner', 'manager', 'inventory_operator'])],
        ]);

        $workspace = $this->workspace($request);
        abort_if($workspace->is_demo, 403);
        $workspace->update($data);

        return to_route('settings.index')->with('status', 'Catalog editing permission updated.');
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }
}
