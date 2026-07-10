<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_memories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->vector('embedding', 1536);
            } else {
                $table->json('embedding');
            }
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();

            $table->index('account_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX agent_memories_embedding_idx
                ON agent_memories USING hnsw (embedding vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS agent_memories_embedding_idx');
        }

        Schema::dropIfExists('agent_memories');
    }
};
