<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synced_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('synced_customer_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('order_number')->nullable();
            $table->string('financial_status')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->unsignedBigInteger('total_price_minor')->default(0);
            $table->string('currency', 3)->nullable();
            $table->jsonb('line_items')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampTz('placed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['store_connection_id', 'external_id']);
            $table->index(['store_connection_id', 'fulfillment_status']);
            $table->index(['store_connection_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_orders');
    }
};
