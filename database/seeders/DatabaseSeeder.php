<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RestaurantFoundationSeeder::class,
        ]);

        $seedLocalDemo = app()->environment('local')
            && filter_var(env('SEED_RESTAURANT_DEMO', false), FILTER_VALIDATE_BOOLEAN);
        $seedProductionDemo = app()->environment('production')
            && filter_var(env('ALLOW_PRODUCTION_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN);

        // Production demo data must be deliberately opted into for this one command.
        // This creates sample orders, payments and expenses; it must never happen by default.
        if ($seedLocalDemo || $seedProductionDemo) {
            $this->call(RestaurantDemoSeeder::class);
        }
    }
}
