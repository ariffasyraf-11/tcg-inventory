<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\StockOperations;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspace($request);

        $items = InventoryItem::query()
            ->where('workspace_id', $workspace->id)
            ->with(['variant.product', 'batches'])
            ->orderBy('id')
            ->get();

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

        return view('inventory.index', compact('workspace', 'items', 'variants', 'movements'));
    }

    public function receive(Request $request, StockOperations $operations): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'unit_cost' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
        ]);

        $operations->receive(
            $request->user(),
            ProductVariant::findOrFail($data['variant_id']),
            (int) $data['quantity'],
            $data['unit_cost']
        );

        return to_route('inventory.index')->with('status', 'Stock received successfully.');
    }

    public function adjust(Request $request, StockOperations $operations): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $operations->adjustOut(
            $request->user(),
            ProductVariant::findOrFail($data['variant_id']),
            (int) $data['quantity'],
            $data['reason']
        );

        return to_route('inventory.index')->with('status', 'Stock adjustment recorded.');
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::query()->firstOrCreate(
            ['owner_user_id' => $request->user()->id],
            ['name' => $request->user()->name."'s inventory", 'is_demo' => false]
        );
    }
}
