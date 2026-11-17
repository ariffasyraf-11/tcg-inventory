<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->boolean('single_sku_enabled')->default(true);
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing SKU-less singles cannot safely be converted to mandatory SKUs.
        // Require explicit data migration before rolling this schema change back.
        throw new RuntimeException('Rollback requires assigning SKUs to all SKU-less variants first.');
    }
};
