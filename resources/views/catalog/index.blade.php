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
        <form action="{{ route('catalog.store') }}" method="POST" class="grid gap-4 md:grid-cols-2">
            @csrf
            <label class="text-sm font-medium">Product name
                <input name="name" value="{{ old('name') }}" required maxlength="255" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Type
                <select name="type" required class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
                    <option value="single" @selected(old('type') === 'single')>Single card</option>
                    <option value="sealed" @selected(old('type') === 'sealed')>Sealed product</option>
                </select>
            </label>
            <label class="text-sm font-medium">SKU @unless($workspace->single_sku_enabled)<span class="text-slate-500">(optional for singles)</span>@endunless
                <input name="sku" value="{{ old('sku') }}" maxlength="100" @if($workspace->single_sku_enabled) required @endif class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
                <span class="mt-1 block text-xs text-slate-500">Sealed products always require SKU. Singles without SKU cannot use SKU-based CSV imports.</span>
            </label>
            <label class="text-sm font-medium">Condition (singles)
                <input name="condition" value="{{ old('condition') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Language
                <input name="language" value="{{ old('language') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Finish (singles)
                <input name="finish" value="{{ old('finish') }}" maxlength="40" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Card number (singles)
                <input name="card_number" value="{{ old('card_number') }}" maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <label class="text-sm font-medium">Sealed format
                <input name="sealed_format" value="{{ old('sealed_format') }}" maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 p-2">
            </label>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700 md:col-span-2">Add product</button>
        </form>
    </section>
    @endif
    <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <h2 class="p-5 text-xl font-semibold">Your products</h2>
        <table class="w-full min-w-[600px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-4">Image</th><th class="p-4">Product</th><th class="p-4">Type</th><th class="p-4">SKU</th><th class="p-4">Variant</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    @foreach ($product->variants as $variant)
                        <tr>
                            <td class="p-4"><div class="flex flex-col gap-2">@if($product->image_path)<img src="{{ route('catalog.image.show', $product) }}" alt="{{ $product->name }} image" loading="lazy" class="h-20 w-20 rounded-lg border object-cover">@else<div class="flex h-20 w-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-500">No image</div>@endif@if($workspace->owner_user_id === auth()->id())<form action="{{ route('catalog.image.store', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-1">@csrf<label class="block text-xs">Image<input name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required class="block w-40 text-xs"></label><button class="rounded bg-slate-800 px-2 py-1 text-xs text-white">Upload</button></form>@if($product->image_path)<form action="{{ route('catalog.image.destroy', $product) }}" method="POST">@csrf @method('DELETE')<button class="text-xs text-red-700 underline">Remove image</button></form>@endif @endif</div></td><td class="p-4 font-semibold">{{ $product->name }} @if(app(\App\Support\WorkspaceAccess::class)->allows(auth()->user(), $workspace, 'catalog_edit'))<a class="ml-2 text-xs font-normal text-indigo-700 underline" href="{{ route('catalog.edit', $product) }}">Edit</a>@endif</td>
                            <td class="p-4">{{ ucfirst($product->type) }}</td>
                            <td class="p-4">{{ $variant->sku ?: 'No SKU' }}</td>
                            <td class="p-4">{{ collect([$variant->condition, $variant->language, $variant->finish, $variant->card_number, $variant->sealed_format])->filter()->join(' · ') ?: 'Standard' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="p-5 text-slate-600">No products yet. Add your first single or sealed product above.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-5">{{ $products->links() }}</div>
    </section>
</x-app-shell></body></html>