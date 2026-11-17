<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Stock management · TCG Inventory</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-slate-50 text-slate-900">
<x-app-shell title="Stock management">
    <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div><p class="text-sm font-semibold uppercase text-indigo-700">TCG Inventory</p><h1 class="text-3xl font-bold">Stock management</h1><p class="text-sm text-slate-600">{{ $workspace->name }}</p></div>
        
    </header>
    @if (session('status'))<p role="status" class="mb-4 rounded-lg bg-emerald-100 p-3">{{ session('status') }}</p>@endif
    @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-100 p-3"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="grid gap-6 md:grid-cols-2">
        <section class="rounded-xl border bg-white p-5"><h2 class="mb-4 text-xl font-semibold">Receive stock</h2>
            <form method="POST" action="{{ route('inventory.receive') }}" class="space-y-4">@csrf
                <label class="block text-sm font-medium">Product variant<select required name="variant_id" class="mt-1 block w-full rounded-lg border p-2"><option value="">Select a product</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected(old('variant_id') == $variant->id)>{{ $variant->product->name }} — {{ $variant->sku ?: 'No SKU · #'.$variant->id }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium">Quantity<input required type="number" min="1" max="2147483647" name="quantity" value="{{ old('quantity', 1) }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                <label class="block text-sm font-medium">Unit cost<input required name="unit_cost" inputmode="decimal" placeholder="0.00" value="{{ old('unit_cost') }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Receive</button>
            </form>
        </section>
        <section class="rounded-xl border bg-white p-5"><h2 class="mb-4 text-xl font-semibold">Adjust stock out</h2>
            <form method="POST" action="{{ route('inventory.adjust') }}" class="space-y-4">@csrf
                <label class="block text-sm font-medium">Product variant<select required name="variant_id" class="mt-1 block w-full rounded-lg border p-2"><option value="">Select a product</option>@foreach($variants as $variant)<option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->sku ?: 'No SKU · #'.$variant->id }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium">Quantity to remove<input required type="number" min="1" max="2147483647" name="quantity" class="mt-1 block w-full rounded-lg border p-2"></label>
                <label class="block text-sm font-medium">Reason<textarea required maxlength="500" name="reason" class="mt-1 block w-full rounded-lg border p-2" placeholder="Damaged, lost, correction…"></textarea></label>
                <button class="rounded-lg bg-slate-800 px-4 py-2 font-semibold text-white">Record adjustment</button>
            </form>
        </section>
    </div>
    <section class="mt-8 overflow-x-auto rounded-xl border bg-white"><h2 class="p-5 text-xl font-semibold">Inventory balances</h2><table class="w-full min-w-[600px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-4">Product</th><th class="p-4">SKU</th><th class="p-4">On hand</th><th class="p-4">Receipt batches</th></tr></thead><tbody>@forelse($items as $item)<tr class="border-t"><td class="p-4">{{ $item->variant->product->name }}</td><td class="p-4">{{ $item->variant->sku ?: 'No SKU · #'.$item->variant->id }}</td><td class="p-4 font-bold">{{ $item->quantity_on_hand }}</td><td class="p-4">{{ $item->batches->count() }}</td></tr>@empty<tr><td colspan="4" class="p-5 text-slate-600">No stock received yet. Add a product in the catalog, then receive stock above.</td></tr>@endforelse</tbody></table></section>
    <section class="mt-8 overflow-x-auto rounded-xl border bg-white"><h2 class="p-5 text-xl font-semibold">Recent stock movements</h2><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-4">Date</th><th class="p-4">Product</th><th class="p-4">Type</th><th class="p-4">Change</th><th class="p-4">Reason</th></tr></thead><tbody>@forelse($movements as $movement)<tr class="border-t"><td class="p-4">{{ $movement->created_at }}</td><td class="p-4">{{ $movement->item->variant->product->name }}</td><td class="p-4">{{ ucfirst($movement->type) }}</td><td class="p-4">{{ $movement->signed_quantity > 0 ? '+' : '' }}{{ $movement->signed_quantity }}</td><td class="p-4">{{ $movement->reason }}</td></tr>@empty<tr><td colspan="5" class="p-5 text-slate-600">No movements recorded yet.</td></tr>@endforelse</tbody></table></section>
</x-app-shell></body></html>