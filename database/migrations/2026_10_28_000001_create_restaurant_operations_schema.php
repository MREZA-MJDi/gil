<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('singleton_key', 32)->default('main')->unique();
            $table->string('name')->default('خانه گیلاسی');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency_label', 20)->default('تومان');
            $table->unsignedInteger('tax_basis_points')->default(0);
            $table->unsignedInteger('service_basis_points')->default(0);
            $table->text('receipt_footer')->nullable();
            $table->unsignedSmallInteger('paper_width')->default(80);
            $table->boolean('customer_menu_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->string('unit', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'status', 'sort_order']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('addon');
            $table->unsignedBigInteger('price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('token', 96)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->nullable()->unique();
            $table->foreignId('restaurant_table_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('channel', 20)->default('dine_in');
            $table->string('status', 20)->default('new');
            $table->string('payment_status', 30)->default('unpaid');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('service')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('idempotency_hash', 64)->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index(['created_at', 'status']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['restaurant_table_id', 'status']);
            $table->index(['channel', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->json('options')->nullable();
            $table->unsignedBigInteger('options_total')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['order_id', 'id']);
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 24);
            $table->string('status', 20)->default('succeeded');
            $table->string('provider', 40)->nullable();
            $table->string('reference', 120)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->string('idempotency_hash', 64);
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();
            $table->index(['order_id', 'status', 'paid_at']);
            $table->index(['method', 'paid_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 24);
            $table->string('reason', 500);
            $table->string('idempotency_key', 64)->unique();
            $table->string('idempotency_hash', 64);
            $table->timestamp('refunded_at')->useCurrent();
            $table->timestamps();
            $table->index(['order_id', 'refunded_at']);
            $table->index(['payment_id', 'refunded_at']);
        });

        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('document_number', 40)->index();
            $table->boolean('is_reprint')->default(false);
            $table->timestamp('printed_at')->useCurrent();
            $table->index(['order_id', 'printed_at']);
        });

        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('open');
            $table->boolean('open_lock')->nullable()->unique();
            $table->unsignedBigInteger('opening_cash')->default(0);
            $table->unsignedBigInteger('expected_cash')->nullable();
            $table->unsignedBigInteger('counted_cash')->nullable();
            $table->bigInteger('difference')->nullable();
            $table->string('opening_note', 500)->nullable();
            $table->string('closing_note', 500)->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'opened_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category', 50);
            $table->unsignedBigInteger('amount');
            $table->string('payment_method', 24);
            $table->boolean('is_paid')->default(true);
            $table->dateTime('paid_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamps();
            $table->index(['paid_at', 'category']);
            $table->index(['is_paid', 'created_at']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('direction', 8);
            $table->string('type', 24);
            $table->unsignedBigInteger('amount');
            $table->string('reason', 500)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['cash_shift_id', 'direction', 'occurred_at']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('customer_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('party_size');
            $table->dateTime('starts_at');
            $table->string('status', 20)->default('pending');
            $table->string('source', 20)->default('staff');
            $table->string('notes', 1000)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['starts_at', 'status']);
            $table->index(['phone', 'starts_at']);
        });

        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('kind', 20)->default('complaint');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->text('admin_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['kind', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100)->index();
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('customer_feedback');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('print_logs');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('restaurant_settings');
    }
};
