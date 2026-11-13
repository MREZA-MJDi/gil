<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->string('cashier_name', 150)->nullable()->after('closed_by');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedBigInteger('cash_in_total')->default(0);
            $table->unsignedBigInteger('cash_out_total')->default(0);
            $table->unsignedInteger('cash_movements_count')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('cancelled_orders_count')->default(0);
            $table->unsignedBigInteger('gross_sales')->default(0);
            $table->unsignedBigInteger('payments_total')->default(0);
            $table->unsignedBigInteger('cash_payments_total')->default(0);
            $table->unsignedBigInteger('card_payments_total')->default(0);
            $table->unsignedBigInteger('other_payments_total')->default(0);
            $table->unsignedBigInteger('refunds_total')->default(0);
            $table->bigInteger('net_collections_total')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropColumn([
                'cashier_name',
                'duration_minutes',
                'cash_in_total',
                'cash_out_total',
                'cash_movements_count',
                'orders_count',
                'cancelled_orders_count',
                'gross_sales',
                'payments_total',
                'cash_payments_total',
                'card_payments_total',
                'other_payments_total',
                'refunds_total',
                'net_collections_total',
            ]);
        });
    }
};
