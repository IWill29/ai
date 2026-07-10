<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synced_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('synced_product_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('sku')->nullable();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->string('currency', 3)->nullable();
            $table->integer('inventory_quantity')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();

            $table->unique(['synced_product_id', 'external_id']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_product_variants');
    }
};
