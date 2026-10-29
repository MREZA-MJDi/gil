<?php

namespace Database\Seeders;

use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantFoundationSeeder extends Seeder
{
    /**
     * Seed only operator-configured foundation data.
     *
     * This seeder intentionally does not invent a catalogue, product prices,
     * ingredients, calories, stock balances, table counts or transaction history.
     * Existing operator-edited records are preserved.
     */
    public function run(): void
    {
        RestaurantSetting::query()->firstOrCreate(['singleton_key' => 'main'], [
            'name' => config('restaurant.name', 'خانه گیلاسی'),
            'phone' => config('restaurant.phone'),
            'address' => config('restaurant.address'),
            'currency_label' => config('restaurant.currency_label', 'تومان'),
            'tax_basis_points' => (int) config('restaurant.tax_basis_points', 0),
            'service_basis_points' => (int) config('restaurant.service_basis_points', 0),
            'receipt_footer' => config('restaurant.receipt_footer', 'از خرید شما سپاسگزاریم'),
            'paper_width' => (int) config('restaurant.paper_width', 80),
            'customer_menu_enabled' => (bool) config('restaurant.customer_menu_enabled', true),
        ]);

        foreach (config('restaurant.initial_table_names', []) as $tableName) {
            $tableName = trim((string) $tableName);
            if ($tableName === '') {
                continue;
            }

            RestaurantTable::query()->firstOrCreate(
                ['name' => $tableName],
                [
                    'token' => Str::random(48),
                    'is_active' => true,
                    'description' => null,
                ],
            );
        }
    }
}
