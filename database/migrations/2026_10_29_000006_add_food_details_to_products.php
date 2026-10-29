<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('ingredients')->nullable();
            $table->json('allergens')->nullable();
            $table->json('dietary_tags')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('preparation_time_minutes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ingredients',
                'allergens',
                'dietary_tags',
                'calories',
                'preparation_time_minutes',
            ]);
        });
    }
};
