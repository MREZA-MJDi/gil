<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->json('homepage_content')->nullable();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('seed_key', 100)->nullable()->unique();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('seed_key', 100)->nullable()->unique();
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('seed_key', 100)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropUnique(['seed_key']);
            $table->dropColumn('seed_key');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['seed_key']);
            $table->dropColumn('seed_key');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['seed_key']);
            $table->dropColumn('seed_key');
        });

        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('homepage_content');
        });
    }
};
