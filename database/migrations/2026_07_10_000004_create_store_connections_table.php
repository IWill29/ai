<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('name');
            $table->string('domain');
            $table->string('status')->default('pending');
            $table->timestampTz('last_synced_at')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['account_id', 'platform', 'domain']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_connections');
    }
};
