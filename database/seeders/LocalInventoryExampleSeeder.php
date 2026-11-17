<?php

namespace Database\Seeders;

use App\Actions\Inventory\StockOperations;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LocalInventoryExampleSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Example seeding is only permitted locally or in tests.');
        }

        $password = env('LOCAL_DEMO_PASSWORD');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set LOCAL_DEMO_PASSWORD to a unique password of at least 12 characters.');
        }

        foreach ([
            ['seller-a@example.test', 'Seller A', 'Seller A Cards', [
                ['Sample Dragon #001', 'single', 'SAMPLE-DRAGON', 12, '3.50'],
                ['Starter Deck A', 'sealed', 'STARTER-DECK', 8, '12.00'],
            ]],
            ['seller-b@example.test', 'Seller B', 'Seller B Cards', [
                ['Sample Mage #014', 'single', 'SAMPLE-MAGE', 4, '1.25'],
                ['Booster Box B', 'sealed', 'BOOSTER-BOX', 2, '48.00'],
            ]],
        ] as [$email, $name, $workspaceName, $products]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make($password)]
            );

            $workspace = Workspace::query()->firstOrCreate(
                ['owner_user_id' => $user->id],
                ['name' => $workspaceName, 'is_demo' => false]
            );

            if ($workspace->is_demo) {
                throw new RuntimeException('Refusing to seed into a demo workspace.');
            }

            foreach ($products as [$productName, $type, $sku, $quantity, $cost]) {
                $product = Product::query()->firstOrCreate(
                    ['workspace_id' => $workspace->id, 'name' => $productName],
                    ['type' => $type, 'active' => true]
                );
                $variant = ProductVariant::query()->firstOrCreate(
                    ['product_id' => $product->id, 'sku' => $sku]
                );

                if (! DB::table('inventory_items')->where('product_variant_id', $variant->id)->exists()) {
                    app(StockOperations::class)->receive($user, $variant, $quantity, $cost);
                }
            }
        }
    }
}
