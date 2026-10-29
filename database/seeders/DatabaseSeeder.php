<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Demo records are opt-in and strictly local; never seed orders into a deployed business database by accident.
        if (app()->environment('local') && (bool) env('SEED_RESTAURANT_DEMO', false)) {
            $this->call(RestaurantDemoSeeder::class);
        }
    }
}
