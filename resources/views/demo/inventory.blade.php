<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Demo · TCG Inventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
        <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-indigo-600">TCG Inventory · Local UX prototype</p>
                <h1 class="text-3xl font-bold tracking-tight">Inventory overview</h1>
                <p class="mt-2 text-sm text-slate-600">Session-only fictional inventory. Nothing here is saved to the database.</p>
            </div>
            <form action="{{ route('demo.reset') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 font-semibold shadow-sm hover:bg-slate-100">Reset demo</button>
            </form>
        </header>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-900">
                <strong>Please correct the form:</strong>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section aria-label="Summary" class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Sample product variants</p>
                <p class="mt-2 text-3xl font-bold">{{ count($items) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Units on hand</p>
                <p class="mt-2 text-3xl font-bold">{{ $totalUnits }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">At or below minimum stock</p>
                <p class="mt-2 text-3xl font-bold">{{ $lowStockCount }}</p>
            </div>
        </section>

        <section class="mb-8 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-xl font-semibold">Products and stock</h2>
            </div>
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">On hand</th><th class="px-5 py-3">Threshold</th><th class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <span class="font-semibold">{{ $item['name'] }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $item['detail'] }}</span>
                            </td>
                            <td class="px-5 py-4">{{ $item['type'] }}</td>
                            <td class="px-5 py-4 font-semibold">{{ $item['quantity'] }}</td>
                            <td class="px-5 py-4">{{ $item['minimum_stock'] }}</td>
                            <td class="px-5 py-4">
                                @if ($item['quantity'] <= $item['minimum_stock'])
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900">Low stock</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-900">In stock</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section aria-label="Stock actions" class="mb-8 grid gap-5 lg:grid-cols-2">
            <form action="{{ route('demo.receive') }}" method="POST" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <h2 class="text-xl font-semibold">Receive stock</h2>
                <p class="mt-1 text-sm text-slate-500">Add a sample receipt.</p>
                <label class="mt-5 block text-sm font-semibold" for="receive-item">Product</label>
                <select id="receive-item" name="item_id" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    @foreach ($items as $item)
                        <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                    @endforeach
                </select>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label for="receive-quantity" class="block text-sm font-semibold">Quantity</label>
                        <input id="receive-quantity" name="quantity" type="number" min="1" max="1000" value="5" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    </div>
                    <div>
                        <label for="unit-cost" class="block text-sm font-semibold">Sample unit cost</label>
                        <input id="unit-cost" name="unit_cost" type="number" min="0" max="99999999" step="0.01" value="2.50" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    </div>
                </div>
                <button type="submit" class="mt-5 w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Receive units</button>
            </form>

            <form action="{{ route('demo.adjust') }}" method="POST" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <h2 class="text-xl font-semibold">Adjust stock</h2>
                <p class="mt-1 text-sm text-slate-500">Increase or decrease with a mandatory reason.</p>
                <label class="mt-5 block text-sm font-semibold" for="adjust-item">Product</label>
                <select id="adjust-item" name="item_id" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    @foreach ($items as $item)
                        <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                    @endforeach
                </select>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label for="direction" class="block text-sm font-semibold">Direction</label>
                        <select id="direction" name="direction" class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                            <option value="out">Remove units</option>
                            <option value="in">Add units</option>
                        </select>
                    </div>
                    <div>
                        <label for="adjust-quantity" class="block text-sm font-semibold">Quantity</label>
                        <input id="adjust-quantity" name="quantity" type="number" min="1" max="1000" value="1" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                    </div>
                </div>
                <label for="reason" class="mt-4 block text-sm font-semibold">Reason</label>
                <input id="reason" name="reason" type="text" required maxlength="200" minlength="3" placeholder="e.g. Damaged packaging" class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                <button type="submit" class="mt-5 w-full rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-700">Record adjustment</button>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-semibold">Movement history</h2>
            <p class="mt-1 text-sm text-slate-500">Entries are scoped to your local demo session and disappear when reset.</p>
            @forelse ($movements as $movement)
                <div class="mt-4 flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
                    <div>
                        <p class="font-semibold">{{ $movement['product'] }} · {{ $movement['type'] }}</p>
                        <p class="mt-1 text-slate-600">{{ $movement['reason'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $movement['at'] }}</p>
                    </div>
                    <span class="{{ $movement['change'] >= 0 ? 'text-emerald-700' : 'text-red-700' }} font-bold">
                        {{ $movement['change'] > 0 ? '+' : '' }}{{ $movement['change'] }}
                    </span>
                </div>
            @empty
                <p class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No movements yet. Try receiving five Sample Dragon cards.</p>
            @endforelse
        </section>
    </main>
</body>
</html>
