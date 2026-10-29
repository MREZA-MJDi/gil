<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 24);
            $table->bigInteger('quantity_delta');
            $table->unsignedInteger('stock_before')->nullable();
            $table->unsignedInteger('stock_after');
            $table->string('reason', 500)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
            $table->index(['order_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
