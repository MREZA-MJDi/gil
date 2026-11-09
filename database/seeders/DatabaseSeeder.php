<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Only seeds the foundational catalogue/settings and the explicitly configured
     * administrator. It does not invent sales, customers, payments, reservations,
     * expenses or inventory movements.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RestaurantFoundationSeeder::class,
        ]);

        // Demo records are opt-in and only allowed in local development.
        if (app()->environment('local') && filter_var(env('SEED_RESTAURANT_DEMO', false), FILTER_VALIDATE_BOOL)) {
            $this->call(RestaurantDemoSeeder::class);
        }
    }
}
