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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $this->workspace($request);

        $status = $request->query('status', 'active');
        $type = $request->query('type', 'single');
        abort_unless(in_array($type, ['single', 'sealed'], true), 404);
        abort_unless(in_array($status, ['active', 'archived', 'all'], true), 404);

        return view('catalog.index', [
            'products' => Product::query()
                ->where('workspace_id', $workspace->id)
                ->where('type', $type)
                ->when($status === 'active', fn ($query) => $query->where('active', true))
                ->when($status === 'archived', fn ($query) => $query->where('active', false))
                ->with('variants')
                ->latest()
                ->paginate(20)->withQueryString(),
            'status' => $status,
            'type' => $type,
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
            'sku' => [$request->input('type') === 'sealed' || ($request->input('type') === 'single' && ($workspace->single_sku_enabled || $request->boolean('use_single_sku'))) ? 'required' : 'nullable', 'string', 'max:100'],
            'use_single_sku' => ['sometimes', 'boolean'],
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

        $product->load('variants');
        $variantIds = $product->variants->modelKeys();

        $attributeChanges = DB::table('product_variant_attribute_changes as changes')
            ->leftJoin('users as actors', 'actors.id', '=', 'changes.actor_user_id')
            ->whereIn('changes.product_variant_id', $variantIds)
            ->select('changes.product_variant_id', 'changes.attribute', 'changes.old_value', 'changes.new_value', 'changes.created_at', 'changes.id', 'actors.name as actor_name')
            ->get()
            ->map(fn ($change) => (object) [
                'variant_id' => $change->product_variant_id,
                'field' => str_replace('_', ' ', $change->attribute),
                'old_value' => $change->old_value,
                'new_value' => $change->new_value,
                'created_at' => $change->created_at,
                'id' => $change->id,
                'actor_name' => $change->actor_name,
                'source' => 'attribute',
            ]);

        $skuChanges = DB::table('product_sku_changes as changes')
            ->leftJoin('users as actors', 'actors.id', '=', 'changes.actor_user_id')
            ->whereIn('changes.product_variant_id', $variantIds)
            ->select('changes.product_variant_id', 'changes.old_sku', 'changes.new_sku', 'changes.created_at', 'changes.id', 'actors.name as actor_name')
            ->get()
            ->map(fn ($change) => (object) [
                'variant_id' => $change->product_variant_id,
                'field' => 'SKU',
                'old_value' => $change->old_sku,
                'new_value' => $change->new_sku,
                'created_at' => $change->created_at,
                'id' => $change->id,
                'actor_name' => $change->actor_name,
                'source' => 'sku',
            ]);

        $changeHistory = $attributeChanges->concat($skuChanges)
            ->sort(fn ($a, $b) => strcmp($b->created_at, $a->created_at) ?: ($b->id <=> $a->id))
            ->values();

        return view('catalog.edit', compact('workspace', 'product', 'changeHistory'));
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

    public function updateSku(Request $request, Product $product): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_if($workspace->is_demo, 403);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog_edit'), 403);

        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'sku' => ['required', 'string', 'max:100'],
        ]);
        $sku = trim((string) ($data['sku'] ?? ''));
        DB::transaction(function () use ($request, $product, $workspace, $data, $sku): void {
            $variant = $product->variants()->whereKey($data['variant_id'])->lockForUpdate()->firstOrFail();
            $oldSku = $variant->sku;
            if ($product->type === 'single' && $oldSku === null) {
                throw ValidationException::withMessages(['sku' => 'SKU editing is unavailable for single cards created without an SKU.']);
            }
            $newSku = $sku !== '' ? $sku : null;
            if ($oldSku === $newSku) {
                return;
            }
            $exists = Product::query()->where('workspace_id', $workspace->id)
                ->whereHas('variants', fn ($query) => $query->where('sku', $newSku)->whereKeyNot($variant->id))
                ->exists();
            if ($newSku !== null && $exists) {
                throw ValidationException::withMessages(['sku' => 'SKU is already in use in this workspace.']);
            }
            $variant->update(['sku' => $newSku]);
            DB::table('product_sku_changes')->insert([
                'product_variant_id' => $variant->id,
                'actor_user_id' => $request->user()->id,
                'old_sku' => $oldSku,
                'new_sku' => $newSku,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return to_route('catalog.edit', $product)->with('status', 'SKU updated. Review any external CSV references to the previous SKU.');
    }

    public function updateVariant(Request $request, Product $product): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_if($workspace->is_demo, 403);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog_edit'), 403);

        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'condition' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:40'],
            'finish' => ['nullable', 'string', 'max:40'],
            'card_number' => ['nullable', 'string', 'max:100'],
            'sealed_format' => ['nullable', 'string', 'max:100'],
        ]);
        $attributes = $product->type === 'single'
            ? ['condition', 'language', 'finish', 'card_number']
            : ['language', 'sealed_format'];

        DB::transaction(function () use ($request, $product, $data, $attributes): void {
            $variant = $product->variants()->whereKey($data['variant_id'])->lockForUpdate()->firstOrFail();
            foreach ($attributes as $attribute) {
                $oldValue = $variant->{$attribute};
                $newValue = trim((string) ($data[$attribute] ?? '')) ?: null;
                if ($oldValue === $newValue) {
                    continue;
                }

                $variant->{$attribute} = $newValue;
                DB::table('product_variant_attribute_changes')->insert([
                    'product_variant_id' => $variant->id,
                    'actor_user_id' => $request->user()->id,
                    'attribute' => $attribute,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $variant->save();
        });

        return to_route('catalog.edit', $product)->with('status', 'Variant details updated and changes recorded.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_if($workspace->is_demo, 403);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'catalog_edit'), 403);

        // Archiving retains variants, stock movements, sales and images for audit purposes.
        $product->update(['active' => false]);

        return to_route('catalog.index')->with('status', 'Product archived (inactive). Inventory and sales history have been preserved.');
    }

    private function workspace(Request $request): Workspace
    {
        return app(WorkspaceContext::class)->current($request);
    }
}
