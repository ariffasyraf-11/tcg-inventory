<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\StockOperations;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Workspace;
use App\Support\WorkspaceContext;
use App\Support\WorkspaceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspace($request);

        $search = trim((string) $request->query('q', ''));

        $items = InventoryItem::query()
            ->where('workspace_id', $workspace->id)
            ->with(['variant.product', 'batches'])
            ->when($search !== '', fn ($query) => $query->whereHas('variant', fn ($variant) => $variant
                ->where('sku', 'like', '%'.$search.'%')
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%'.$search.'%'))))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->with('product')
            ->orderBy('id')
            ->get();

        $movements = StockMovement::query()
            ->whereHas('item', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->with(['item.variant.product', 'batch'])
            ->latest('id')
            ->limit(30)
            ->get();

        return view('inventory.index', compact('workspace', 'items', 'variants', 'movements', 'search'));
    }

    public function show(Request $request, InventoryItem $item): View
    {
        $workspace = $this->workspace($request);
        abort_unless($item->workspace_id === $workspace->id, 404);

        $item->load(['variant.product', 'batches']);
        $movements = $item->movements()->latest('id')->paginate(30);

        return view('inventory.show', compact('workspace', 'item', 'movements'));
    }

    public function receive(Request $request, StockOperations $operations): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'stock'), 403);
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'unit_cost' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
        ]);

        $operations->receive(
            $request->user(),
            ProductVariant::findOrFail($data['variant_id']),
            (int) $data['quantity'],
            $data['unit_cost'],
            $workspace
        );

        return to_route('inventory.index')->with('status', 'Stock received successfully.');
    }

    public function adjust(Request $request, StockOperations $operations): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'stock'), 403);
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $operations->adjustOut(
            $request->user(),
            ProductVariant::findOrFail($data['variant_id']),
            (int) $data['quantity'],
            $data['reason'],
            $workspace
        );

        return to_route('inventory.index')->with('status', 'Stock adjustment recorded.');
    }

    private function workspace(Request $request): Workspace
    {
        return app(WorkspaceContext::class)->current($request);
    }
}
