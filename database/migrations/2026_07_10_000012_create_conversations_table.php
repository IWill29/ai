<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('store_connection_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('model')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
