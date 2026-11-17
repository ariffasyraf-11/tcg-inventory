<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\WorkspaceAccess;
use App\Support\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageController extends Controller
{
    public function show(Request $request, Product $product): BinaryFileResponse
    {
        $workspace = app(WorkspaceContext::class)->current($request);
        abort_unless($product->workspace_id === $workspace->id, 404);
        abort_unless(app(WorkspaceAccess::class)->allows($request->user(), $workspace, 'view'), 404);

        abort_unless($product->image_path && Storage::disk('local')->exists($product->image_path), 404);

        return response()->file(Storage::disk('local')->path($product->image_path), [
            'Content-Type' => Storage::disk('local')->mimeType($product->image_path),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        abort_if($product->workspace->is_demo, 403);

        $data = $request->validate([
            'image' => ['required', File::image()->max('3mb')],
        ]);

        $newPath = $data['image']->store('product-images/'.$product->workspace_id, 'local');
        $oldPath = $product->image_path;

        try {
            $product->update(['image_path' => $newPath]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return to_route('catalog.index')->with('status', 'Product image updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeOwner($request, $product);
        abort_if($product->workspace->is_demo, 403);

        $oldPath = $product->image_path;
        $product->update(['image_path' => null]);

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return to_route('catalog.index')->with('status', 'Product image removed.');
    }

    private function authorizeOwner(Request $request, Product $product): void
    {
        abort_unless($product->workspace->owner_user_id === $request->user()->id, 404);
    }
}
