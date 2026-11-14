<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservations') || Schema::hasColumn('reservations', 'customer_id')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reservations') || ! Schema::hasColumn('reservations', 'customer_id')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
