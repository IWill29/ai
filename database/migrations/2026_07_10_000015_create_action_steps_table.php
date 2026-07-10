<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('tool_name');
            $table->jsonb('arguments')->nullable();
            $table->string('target_platform')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_write')->default(false);
            $table->boolean('confirmed')->nullable();
            $table->jsonb('result_summary')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestampsTz();

            $table->index(['message_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_steps');
    }
};
