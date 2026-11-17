<?php

namespace App\Http\Controllers;

use App\Actions\Sales\RecordSale;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = DB::table('workspaces')->where('owner_user_id', $request->user()->id)->first();

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('workspace_id', $workspace?->id))
            ->with('product')->orderBy('id')->get();

        $sales = DB::table('sales')->where('workspace_id', $workspace?->id)->orderByDesc('id')->limit(30)->get();

        return view('sales.index', compact('variants', 'sales'));
    }

    public function store(Request $request, RecordSale $service): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit_price' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $id = $service->execute(
            $request->user(), ProductVariant::findOrFail($data['variant_id']),
            (int) $data['quantity'], $data['unit_price'], $data['reference'] ?? null
        );

        return to_route('sales.index')->with('status', 'Sale #'.$id.' recorded.');
    }
}
