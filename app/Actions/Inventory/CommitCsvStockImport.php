<?php

namespace App\Actions\Inventory;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommitCsvStockImport
{
    public function __construct(private readonly StockOperations $stock) {}

    public function execute(User $actor, array $staged): void
    {
        DB::transaction(function () use ($actor, $staged): void {
            $workspace = DB::table('workspaces')->where('owner_user_id', $actor->id)
                ->where('id', $staged['workspace_id'])->where('is_demo', false)->lockForUpdate()->first();

            if (! $workspace || empty($staged['rows']) || count($staged['rows']) > 500) {
                throw ValidationException::withMessages(['csv' => 'Import is no longer valid.']);
            }

            foreach ($staged['rows'] as $row) {
                $matches = DB::table('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->where('products.workspace_id', $workspace->id)
                    ->where('product_variants.sku', $row['sku'])
                    ->where('product_variants.id', $row['variant_id'])->count();

                $total = DB::table('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->where('products.workspace_id', $workspace->id)
                    ->where('product_variants.sku', $row['sku'])->count();

                if ($matches !== 1 || $total !== 1 || ! ctype_digit((string) $row['quantity'])
                    || (int) $row['quantity'] < 1 || (int) $row['quantity'] > 2147483647
                    || ! preg_match('/^\d{1,10}(\.\d{1,2})?$/', (string) $row['cost'])) {
                    throw ValidationException::withMessages(['csv' => 'Catalog or CSV data changed. Preview again.']);
                }

                $this->stock->receive($actor, ProductVariant::findOrFail($row['variant_id']),
                    (int) $row['quantity'], (string) $row['cost']);
            }
        }, 3);
    }
}
