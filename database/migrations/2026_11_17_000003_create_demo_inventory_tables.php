<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('demo_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sample_key', 40);
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['user_id', 'sample_key']);
        });
        Schema::create('demo_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sample_key', 40);
            $table->integer('signed_quantity');
            $table->string('reason', 200);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('demo_stock_movements');
        Schema::dropIfExists('demo_stock');
    }
};
