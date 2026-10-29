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
    }
}
