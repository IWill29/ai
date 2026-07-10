<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retention: prune successfully processed events after 90 days via scheduled
 * `webhooks:prune` command (see database-schema.md Step 10b). Keep failed rows for review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_connection_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('topic');
            $table->string('external_event_id')->nullable();
            $table->string('status')->default('received');
            $table->jsonb('payload');
            $table->text('error')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['store_connection_id', 'external_event_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
