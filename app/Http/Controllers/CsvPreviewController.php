<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\CommitCsvStockImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CsvPreviewController extends Controller
{
    public function index(): View
    {
        return view('imports.preview', ['rows' => [], 'issues' => [], 'headers' => [], 'token' => null]);
    }

    public function preview(Request $request): View
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        $handle = fopen($request->file('csv')->getRealPath(), 'rb');
        abort_unless($handle !== false, 422);

        $headers = [];
        $rows = [];
        $issues = [];
        $seen = [];

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '\\') ?: [];
            $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headers);
            $expected = ['sku', 'quantity', 'unit_cost'];

            if ($headers !== $expected) {
                $issues[] = 'Header must be exactly: sku,quantity,unit_cost';
            } else {
                $line = 1;

                while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                    $line++;

                    if ($line > 501) {
                        $issues[] = 'Maximum 500 data rows per preview.';
                        break;
                    }

                    if (count($values) !== 3) {
                        $issues[] = 'Row '.$line.': expected three columns.';

                        continue;
                    }

                    [$sku, $quantity, $cost] = array_map('trim', $values);
                    $error = null;

                    if ($sku === '' || strlen($sku) > 100) {
                        $error = 'Invalid SKU.';
                    } elseif (! ctype_digit($quantity) || (int) $quantity < 1 || (float) $quantity > 2147483647) {
                        $error = 'Quantity must be a positive integer.';
                    } elseif (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $cost)) {
                        $error = 'Invalid unit cost.';
                    } elseif (isset($seen[$sku])) {
                        $error = 'Duplicate SKU in file.';
                    }

                    $seen[$sku] = true;
                    $rows[] = compact('line', 'sku', 'quantity', 'cost', 'error');

                    if ($error !== null) {
                        $issues[] = 'Row '.$line.': '.$error;
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        $workspace = DB::table('workspaces')->where('owner_user_id', $request->user()->id)->where('is_demo', false)->first();
        $token = null;

        if ($workspace && $rows !== [] && $issues === []) {
            $skus = array_column($rows, 'sku');
            $matches = DB::table('product_variants')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('products.workspace_id', $workspace->id)
                ->whereIn('product_variants.sku', $skus)
                ->select('product_variants.id', 'product_variants.sku')->get()->groupBy('sku');

            foreach ($rows as &$row) {
                $found = $matches->get($row['sku'], collect());

                if ($found->count() !== 1) {
                    $row['error'] = $found->isEmpty() ? 'SKU not found in your catalog.' : 'Ambiguous SKU in your catalog.';
                    $issues[] = 'Row '.$row['line'].': '.$row['error'];
                } else {
                    $row['variant_id'] = $found->first()->id;
                }
            }
            unset($row);

            if ($issues === []) {
                $token = Str::random(40);
                $request->session()->put('csv_stock_import', [
                    'token' => $token, 'owner_id' => $request->user()->id,
                    'workspace_id' => $workspace->id, 'rows' => $rows,
                ]);
            }
        }

        if ($token === null) {
            $request->session()->forget('csv_stock_import');
        }

        return view('imports.preview', compact('rows', 'issues', 'headers', 'token'));
    }
    public function commit(Request $request, CommitCsvStockImport $import): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:40']]);
        $staged = $request->session()->get('csv_stock_import');

        if (! is_array($staged) || ! hash_equals((string) ($staged['token'] ?? ''), $data['token'])
            || ($staged['owner_id'] ?? null) !== $request->user()->id) {
            throw ValidationException::withMessages(['csv' => 'Preview expired. Upload the file again.']);
        }

        $import->execute($request->user(), $staged);
        $request->session()->forget('csv_stock_import');

        return to_route('imports.index')->with('status', 'CSV stock received successfully.');
    }
}
