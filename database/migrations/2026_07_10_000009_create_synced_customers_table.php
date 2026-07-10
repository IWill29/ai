<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synced_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->integer('orders_count')->default(0);
            $table->unsignedBigInteger('total_spent_minor')->default(0);
            $table->string('currency', 3)->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();

            $table->unique(['store_connection_id', 'external_id']);
            $table->index(['store_connection_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_customers');
    }
};
