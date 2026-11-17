<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_login_and_does_not_write_stock(): void
    {
        $file = UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nCARD-1,2,3.50\n");
        $this->post('/imports/preview', ['csv' => $file])->assertRedirect('/login');

        $this->actingAs(User::factory()->create())->post('/imports/preview', [
            'csv' => UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nCARD-1,2,3.50\n"),
        ])->assertOk()->assertSee('CARD-1')->assertSee('Valid format');

        $this->assertDatabaseCount('inventory_items', 0);
        $this->assertDatabaseCount('inventory_batches', 0);
    }

    public function test_preview_flags_duplicate_sku_and_bad_quantity(): void
    {
        $this->actingAs(User::factory()->create())->post('/imports/preview', [
            'csv' => UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nCARD-1,2,3.50\nCARD-1,1,3.50\nCARD-2,-1,2.00\n"),
        ])->assertOk()->assertSee('Duplicate SKU in file')->assertSee('Quantity must be a positive integer.');
    }
}
