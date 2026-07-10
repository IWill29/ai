<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->integer('price_cents')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->integer('store_limit')->nullable();
            $table->integer('monthly_message_limit')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
