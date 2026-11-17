<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['workspace_id', 'type']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('condition', 40)->nullable();
            $table->string('language', 40)->nullable();
            $table->string('finish', 40)->nullable();
            $table->string('card_number', 100)->nullable();
            $table->string('sealed_format', 100)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('games');
        Schema::dropIfExists('workspaces');
    }
};
