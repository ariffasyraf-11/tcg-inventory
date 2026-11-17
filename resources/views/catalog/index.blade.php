<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product catalog · TCG Inventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
<x-app-shell title="Product catalog">
    <header class="mb-8">
        
        <p class="text-sm font-semibold uppercase tracking-wider text-indigo-700">TCG Inventory</p>
        <h1 class="mt-1 text-3xl font-bold">Product catalog</h1>
        
        <p class="mt-2 text-sm text-slate-600">Private workspace: {{ $workspace->name }}. Manage stock from the inventory screen.</p>
    </header>
    @if (session('status'))
        <p role="status" class="mb-4 rounded-lg bg-emerald-100 p-3 text-emerald-900">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg bg-red-100 p-3 text-red-900">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(app(\App\Support\WorkspaceAccess::class)->allows(auth()->user(), $workspace, 'catalog'))
    <section class="mb-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold">Add a product</h2>
        <form id="catalog-create-form" action="{{ route('catalog.store') }}" method="POST" class="grid gap-4 md:grid-cols-2">
            @csrf
            <label class="text-sm font-medium">Product name
                <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Type
                <select name="type" id="catalog-product-type" required class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
                    <option value="single" @selected(old('type') === 'single')>Single card</option>
                    <option value="sealed" @selected(old('type') === 'sealed')>Sealed product</option>
                </select>
            </label>
            <label class="text-sm font-medium">SKU <span class="text-slate-500">(optional for singles when enabled in workspace settings)</span>
                <input id="catalog-sku" name="sku" value="{{ old('sku') }}" maxlength="100" @if($workspace->single_sku_enabled) required @endif class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
                <span class="mt-1 block text-xs text-slate-500">Sealed products always require SKU. Singles without SKU cannot use SKU-based CSV imports.</span>
            </label>
            <label class="text-sm font-medium">Condition (singles)
                <input data-single-field name="condition" list="catalog-condition-suggestions" value="{{ old('condition') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Language
                <input name="language" list="catalog-language-suggestions" value="{{ old('language') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Finish (singles)
                <input data-single-field name="finish" list="catalog-finish-suggestions" value="{{ old('finish') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Card number (singles)
                <input data-single-field name="card_number" value="{{ old('card_number') }}" maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Sealed format
                <input data-sealed-field name="sealed_format" list="catalog-sealed-format-suggestions" value="{{ old('sealed_format') }}" maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <datalist id="catalog-condition-suggestions"><option value="Near Mint"></option><option value="Lightly Played"></option><option value="Moderately Played"></option><option value="Heavily Played"></option><option value="Damaged"></option></datalist>
            <datalist id="catalog-language-suggestions"><option value="English"></option><option value="Japanese"></option></datalist>
            <datalist id="catalog-finish-suggestions"><option value="Non-Foil"></option><option value="Foil"></option><option value="Reverse Holo"></option><option value="Holofoil"></option><option value="Full Art"></option><option value="Textured"></option></datalist>
            <datalist id="catalog-sealed-format-suggestions"><option value="Booster Pack"></option><option value="Booster Box"></option><option value="Elite Trainer Box"></option><option value="Starter Deck"></option><option value="Structure Deck"></option><option value="Theme Deck"></option><option value="Collection Box"></option><option value="Tin"></option><option value="Bundle"></option></datalist>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700 md:col-span-2">Add product</button>
        </form>
    </section>
    @endif
    <nav aria-label="Product categories" class="mb-4 flex gap-2 border-b border-slate-200" role="tablist">
        <a role="tab" aria-selected="{{ $type === 'single' ? 'true' : 'false' }}" href="{{ route('catalog.index', ['type' => 'single', 'status' => $status]) }}" class="rounded-t-lg px-5 py-3 text-sm font-semibold {{ $type === 'single' ? 'border-b-2 border-indigo-600 bg-white text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Singles &amp; Used</a>
        <a role="tab" aria-selected="{{ $type === 'sealed' ? 'true' : 'false' }}" href="{{ route('catalog.index', ['type' => 'sealed', 'status' => $status]) }}" class="rounded-t-lg px-5 py-3 text-sm font-semibold {{ $type === 'sealed' ? 'border-b-2 border-indigo-600 bg-white text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Sealed Products</a>
    </nav>
    <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5"><h2 class="text-xl font-semibold">{{ $type === 'single' ? 'Singles & Used' : 'Sealed Products' }}</h2><nav aria-label="Catalog status filter" class="flex gap-3 text-sm"><a href="{{ route('catalog.index', ['type' => $type, 'status' => 'active']) }}" class="{{ $status === 'active' ? 'font-bold text-indigo-700' : 'text-slate-600' }}">Active</a><a href="{{ route('catalog.index', ['type' => $type, 'status' => 'archived']) }}" class="{{ $status === 'archived' ? 'font-bold text-indigo-700' : 'text-slate-600' }}">Archived</a><a href="{{ route('catalog.index', ['type' => $type, 'status' => 'all']) }}" class="{{ $status === 'all' ? 'font-bold text-indigo-700' : 'text-slate-600' }}">All</a></nav></div>
        <table class="w-full min-w-[600px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-4">Image</th><th class="p-4">Product</th><th class="p-4">Type</th><th class="p-4">SKU</th><th class="p-4">Variant</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    @foreach ($product->variants as $variant)
                        <tr>
                            <td class="p-4"><div class="flex flex-col gap-2">@if($product->image_path)<img src="{{ route('catalog.image.show', $product) }}" alt="{{ $product->name }} image" loading="lazy" class="h-20 w-20 rounded-lg border object-cover">@else<div class="flex h-20 w-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-500">No image</div>@endif
@if($workspace->owner_user_id === auth()->id())<form action="{{ route('catalog.image.store', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-1">@csrf<label class="block text-xs">Image<input name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required class="block w-40 text-xs"></label><button class="rounded bg-slate-800 px-2 py-1 text-xs text-white">Upload</button></form>@if($product->image_path)<form action="{{ route('catalog.image.destroy', $product) }}" method="POST">@csrf @method('DELETE')<button class="text-xs text-red-700 underline">Remove image</button></form>@endif @endif</div></td><td class="p-4 font-semibold">{{ $product->name }} @unless($product->active)<span class="text-xs text-amber-700">(Archived)</span>@endunless @if(app(\App\Support\WorkspaceAccess::class)->allows(auth()->user(), $workspace, 'catalog_edit'))<a class="ml-2 text-xs font-normal text-indigo-700 underline" href="{{ route('catalog.edit', $product) }}">Edit</a>@endif</td>
                            <td class="p-4">{{ ucfirst($product->type) }}</td>
                            <td class="p-4">{{ $variant->sku ?: 'No SKU · Variant #'.$variant->id }}</td>
                            <td class="p-4">{{ collect([$variant->condition, $variant->language, $variant->finish, $variant->card_number, $variant->sealed_format])->filter()->join(' · ') ?: 'Standard' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="p-5 text-slate-600">No products match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-5">{{ $products->links() }}</div>
    </section>
</x-app-shell><script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('catalog-create-form');
    if (!form) return;
    const type = document.getElementById('catalog-product-type');
    const sku = document.getElementById('catalog-sku');
    const singleSkuRequired = @json((bool) $workspace->single_sku_enabled);
    function syncTypeFields() {
        const sealed = type.value === 'sealed';
        form.querySelectorAll('[data-single-field]').forEach(input => {
            input.disabled = sealed;
            input.closest('label').classList.toggle('opacity-50', sealed);
        });
        form.querySelectorAll('[data-sealed-field]').forEach(input => {
            input.disabled = !sealed;
            input.closest('label').classList.toggle('opacity-50', !sealed);
        });
        sku.required = sealed || singleSkuRequired;
    }
    type.addEventListener('change', syncTypeFields);
    syncTypeFields();
});
</script></body></html>