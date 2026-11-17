<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Workspace;
use App\Support\WorkspaceAccess;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspace($request);

        return view('catalog.index', [
            'products' => Product::query()
                ->where('workspace_id', $workspace->id)
                ->with('variants')
                ->latest()
                ->paginate(20),
            'workspace' => $workspace,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_if($workspace->is_demo, 403, 'Demo workspaces are read-only.');
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['single', 'sealed'])],
            'sku' => [$workspace->single_sku_enabled || $request->input('type') === 'sealed' ? 'required' : 'nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:40'],
            'finish' => ['nullable', 'string', 'max:40'],
            'card_number' => ['nullable', 'string', 'max:100'],
            'sealed_format' => ['nullable', 'string', 'max:100'],
        ]);

        if ($data['type'] === 'single' && ! empty($data['sealed_format'])) {
            return back()->withErrors(['sealed_format' => 'Sealed format is only for sealed products.'])->withInput();
        }

        if ($data['type'] === 'sealed' && (! empty($data['condition']) || ! empty($data['finish']) || ! empty($data['card_number']))) {
            return back()->withErrors(['type' => 'Card-specific attributes are only for singles.'])->withInput();
        }

        $sku = trim((string) ($data['sku'] ?? ''));

        $skuExists = $sku !== '' && Product::query()
            ->where('workspace_id', $workspace->id)
            ->whereHas('variants', fn ($query) => $query->where('sku', $sku))
            ->exists();

        if ($skuExists) {
            return back()->withErrors(['sku' => 'SKU is already in use in your workspace.'])->withInput();
        }

        DB::transaction(function () use ($workspace, $data, $sku): void {
            $product = $workspace->products()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'active' => true,
            ]);

            $product->variants()->create([
                'sku' => $sku !== '' ? $sku : null,
                'condition' => $data['type'] === 'single' ? ($data['condition'] ?? null) : null,
                'language' => $data['language'] ?? null,
                'finish' => $data['type'] === 'single' ? ($data['finish'] ?? null) : null,
                'card_number' => $data['type'] === 'single' ? ($data['card_number'] ?? null) : null,
                'sealed_format' => $data['type'] === 'sealed' ? ($data['sealed_format'] ?? null) : null,
            ]);
        });

        return to_route('catalog.index')->with('status', 'Product added to your catalog.');
    }

    public function edit(Request $request, Product $product): View
    {
        $workspace = $this->workspace($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog_edit'), 403);

        return view('catalog.edit', compact('workspace', 'product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_if($workspace->is_demo, 403);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog_edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ]);
        $product->update($data);

        return to_route('catalog.index')->with('status', 'Product details updated.');
    }

    private function workspace(Request $request): Workspace
    {
        return app(WorkspaceContext::class)->current($request);
    }
}
