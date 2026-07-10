<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openrouter_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->text('api_key');
            $table->string('default_model')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->timestampsTz();

            $table->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openrouter_credentials');
    }
};
