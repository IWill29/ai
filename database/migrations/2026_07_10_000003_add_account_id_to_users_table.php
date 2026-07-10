<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('account_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropColumn('role');
        });
    }
};
