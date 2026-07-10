<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_connection_id')->constrained()->cascadeOnDelete();
            $table->text('access_token');
            $table->text('secrets')->nullable();
            $table->timestampsTz();

            $table->unique('store_connection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_credentials');
    }
};
