<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 24)->default('staff')->after('channel');
            $table->string('payment_preference', 24)->default('at_counter')->after('channel');
            $table->string('external_provider', 32)->nullable()->after('source');
            $table->string('external_order_id', 120)->nullable()->after('external_provider');
            $table->unique(['external_provider', 'external_order_id'], 'orders_external_provider_order_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('recipe_snapshot')->nullable()->after('options');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('purpose', 32)->default('table')->after('source');
            $table->string('occasion_name', 150)->nullable()->after('purpose');
            $table->unsignedBigInteger('estimated_budget')->nullable()->after('occasion_name');
            $table->index(['purpose', 'starts_at', 'status'], 'reservations_purpose_start_status_index');
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('unit', 12);
            $table->decimal('stock_quantity', 14, 3)->default(0);
            $table->decimal('low_stock_threshold', 14, 3)->nullable();
            $table->unsignedBigInteger('cost_per_unit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'stock_quantity']);
        });

        Schema::create('product_recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->unique(['product_id', 'ingredient_id'], 'product_recipe_product_ingredient_unique');
            $table->index(['ingredient_id', 'product_id']);
        });

        Schema::create('ingredient_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 24);
            $table->decimal('quantity_delta', 14, 3);
            $table->decimal('stock_before', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->string('reason', 500);
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('idempotency_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ingredient_id', 'id']);
            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_inventory_movements');
        Schema::dropIfExists('product_recipe_ingredients');
        Schema::dropIfExists('ingredients');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_purpose_start_status_index');
            $table->dropColumn(['purpose', 'occasion_name', 'estimated_budget']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('recipe_snapshot');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_external_provider_order_unique');
            $table->dropColumn(['source', 'external_provider', 'external_order_id', 'payment_preference']);
        });
    }
};
