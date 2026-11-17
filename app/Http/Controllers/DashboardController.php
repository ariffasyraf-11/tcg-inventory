<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = DB::table('workspaces')->where('owner_user_id', $request->user()->id)->first();
        $workspaceId = $workspace?->id;

        $items = DB::table('inventory_items')
            ->join('product_variants', 'product_variants.id', '=', 'inventory_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('inventory_items.workspace_id', $workspaceId)
            ->select('inventory_items.*', 'products.name', 'product_variants.sku')
            ->orderBy('products.name')->get();

        $sales = DB::table('sales')->where('workspace_id', $workspaceId);

        return view('dashboard.index', [
            'items' => $items,
            'variantCount' => $items->count(),
            'totalUnits' => $items->sum('quantity_on_hand'),
            'lowStock' => $items->filter(fn ($item) => $item->quantity_on_hand <= $item->minimum_stock_level),
            'saleCount' => (clone $sales)->count(),
            'revenue' => (clone $sales)->sum('total_revenue'),
            'cost' => (clone $sales)->sum('total_cost'),
        ]);
    }

    public function threshold(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'minimum_stock_level' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        $workspace = DB::table('workspaces')->where('owner_user_id', $request->user()->id)->first();

        $updated = DB::table('inventory_items')
            ->where('workspace_id', $workspace?->id)
            ->where('id', $data['inventory_item_id'])
            ->update(['minimum_stock_level' => $data['minimum_stock_level'], 'updated_at' => now()]);

        abort_unless($updated === 1, 403);

        return to_route('dashboard.index')->with('status', 'Low-stock threshold updated.');
    }
}
