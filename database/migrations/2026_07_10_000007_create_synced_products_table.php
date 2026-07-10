<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synced_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('handle')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampTz('platform_created_at')->nullable();
            $table->timestampTz('platform_updated_at')->nullable();
            $table->timestampsTz();

            $table->unique(['store_connection_id', 'external_id']);
            $table->index(['store_connection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synced_products');
    }
};
