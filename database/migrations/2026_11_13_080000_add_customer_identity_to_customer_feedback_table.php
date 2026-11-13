<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unique(
                ['customer_id', 'order_id'],
                'customer_feedback_customer_order_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customer_feedback', function (Blueprint $table): void {
            $table->dropUnique('customer_feedback_customer_order_unique');
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
