<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CsvCommitTest extends TestCase
{
    use RefreshDatabase;

    private function variant(User $user, string $sku): ProductVariant
    {
        $workspace = Workspace::firstOrCreate(['owner_user_id' => $user->id], ['name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => $sku, 'type' => 'single']);

        return ProductVariant::create(['product_id' => $product->id, 'sku' => $sku]);
    }

    public function test_confirmed_preview_receives_all_rows_and_cannot_be_replayed(): void
    {
        $owner = User::factory()->create();
        $this->variant($owner, 'CARD-1');
        $this->variant($owner, 'CARD-2');
        $this->actingAs($owner)->post('/imports/preview', [
            'csv' => UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nCARD-1,2,3.50\nCARD-2,4,5.00\n"),
        ])->assertOk()->assertSee('Confirm stock import');

        $token = session('csv_stock_import.token');
        $this->post('/imports/commit', ['token' => $token])->assertRedirect('/imports/preview');
        $this->assertSame(6, (int) DB::table('inventory_items')->sum('quantity_on_hand'));
        $this->assertDatabaseCount('inventory_batches', 2);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->post('/imports/commit', ['token' => $token])->assertSessionHasErrors('csv');
        $this->assertDatabaseCount('inventory_batches', 2);
    }

    public function test_unknown_sku_blocks_confirmation_and_cross_owner_stock(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->variant($other, 'OTHER-1');

        $this->actingAs($owner)->post('/imports/preview', [
            'csv' => UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nOTHER-1,2,3.50\n"),
        ])->assertOk()->assertSee('SKU not found in your catalog.')->assertDontSee('Confirm stock import');

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_failure_in_later_row_rolls_back_all_receipts(): void
    {
        $owner = User::factory()->create();
        $this->variant($owner, 'CARD-1');
        $second = $this->variant($owner, 'CARD-2');
        $this->actingAs($owner)->post('/imports/preview', [
            'csv' => UploadedFile::fake()->createWithContent('stock.csv', "sku,quantity,unit_cost\nCARD-1,2,3.50\nCARD-2,4,5.00\n"),
        ])->assertOk();

        $token = session('csv_stock_import.token');
        $second->delete();

        $this->post('/imports/commit', ['token' => $token])->assertSessionHasErrors('csv');
        $this->assertDatabaseCount('inventory_items', 0);
        $this->assertDatabaseCount('inventory_batches', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
