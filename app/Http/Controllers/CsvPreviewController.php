<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CsvPreviewController extends Controller
{
    public function index(): View
    {
        return view('imports.preview', ['rows' => [], 'issues' => [], 'headers' => []]);
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

        return view('imports.preview', compact('rows', 'issues', 'headers'));
    }
}
