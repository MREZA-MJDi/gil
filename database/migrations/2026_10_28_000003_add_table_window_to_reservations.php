<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('restaurant_table_id')->nullable()->after('reference')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->default(90)->after('starts_at');
            $table->dateTime('ends_at')->nullable()->after('duration_minutes');
            $table->index(['restaurant_table_id', 'starts_at', 'ends_at', 'status'], 'reservations_table_window_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_table_window_status_index');
            $table->dropConstrainedForeignId('restaurant_table_id');
            $table->dropColumn(['duration_minutes', 'ends_at']);
        });
    }
};
