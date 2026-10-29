<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantFoundationSeeder extends Seeder
{
    /**
     * Seed the initial catalogue and room configuration. This deliberately does
     * not create fabricated orders, payments, expenses, reservations or feedback.
     * Existing operator-edited records are preserved.
     */
    public function run(): void
    {
        RestaurantSetting::query()->firstOrCreate(['singleton_key' => 'main'], [
            'name' => env('RESTAURANT_NAME', 'خانه گیلاسی'),
            'phone' => env('RESTAURANT_PHONE'),
            'address' => env('RESTAURANT_ADDRESS'),
            'currency_label' => 'تومان',
            'tax_basis_points' => 0,
            'service_basis_points' => 0,
            'receipt_footer' => 'از خرید شما سپاسگزاریم',
            'paper_width' => 80,
            'customer_menu_enabled' => true,
        ]);

        $catalogue = [
            'قهوه گرم' => [
                ['اسپرسو', 'شات قهوه تازه‌عصاره‌گیری‌شده', 65000, 'فنجان', 'قهوه', 2, 100, [['تک‌شات', 'size', 0], ['دبل‌شات', 'size', 25000], ['شیر اضافه', 'addon', 15000]]],
                ['آمریکانو', 'اسپرسو و آب داغ', 70000, 'فنجان', 'قهوه، آب', 3, 150, []],
                ['کاپوچینو', 'اسپرسو، شیر و فوم شیر', 90000, 'فنجان', 'قهوه، شیر', 3, 180, [['شیر گیاهی', 'addon', 25000]]],
                ['لاته', 'اسپرسو با شیر بخارداده‌شده', 95000, 'فنجان', 'قهوه، شیر', 4, 220, [['شیر گیاهی', 'addon', 25000], ['شات اضافه', 'addon', 25000]]],
            ],
            'نوشیدنی سرد' => [
                ['آیس آمریکانو', 'اسپرسو، آب و یخ', 75000, 'لیوان', 'قهوه، آب، یخ', 2, 100, []],
                ['آیس لاته', 'اسپرسو، شیر سرد و یخ', 100000, 'لیوان', 'قهوه، شیر، یخ', 3, 180, [['شیر گیاهی', 'addon', 25000], ['شات اضافه', 'addon', 25000]]],
                ['چای ماسالا', 'چای ادویه‌دار با شیر', 85000, 'لیوان', 'چای، شیر، ادویه', 4, 220, [['شیر گیاهی', 'addon', 25000]]],
            ],
            'صبحانه و میان‌وعده' => [
                ['کروسان کره‌ای', 'کروسان تازه برای صبحانه یا میان‌وعده', 80000, 'عدد', 'آرد گندم، کره، تخم‌مرغ', 1, 300, []],
                ['ساندویچ مرغ', 'ساندویچ مرغ با سبزیجات تازه', 145000, 'عدد', 'مرغ، نان، سبزیجات، سس', 8, 450, []],
            ],
            'کیک و دسر' => [
                ['چیزکیک روز', 'برش چیزکیک؛ موجودی روزانه را بررسی کنید', 115000, 'برش', 'پنیر، تخم‌مرغ، شکر، پایه بیسکویتی', 1, 380, []],
            ],
        ];

        $sort = 0;
        foreach ($catalogue as $categoryName => $items) {
            $category = Category::query()->firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $sort++, 'is_active' => true],
            );

            foreach ($items as $index => [$name, $description, $price, $unit, $ingredients, $prepMinutes, $calories, $options]) {
                $product = Product::query()->firstOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    [
                        'description' => $description,
                        'ingredients' => $ingredients,
                        'allergens' => [],
                        'dietary_tags' => [],
                        'calories' => $calories,
                        'preparation_time_minutes' => $prepMinutes,
                        'base_price' => $price,
                        'unit' => $unit,
                        'status' => 'active',
                        'sort_order' => $index,
                        'stock_quantity' => null,
                        'low_stock_threshold' => null,
                    ],
                );

                foreach ($options as $optionIndex => [$optionName, $type, $optionPrice]) {
                    ProductOption::query()->firstOrCreate(
                        ['product_id' => $product->id, 'name' => $optionName],
                        [
                            'type' => $type,
                            'price' => $optionPrice,
                            'is_active' => true,
                            'sort_order' => $optionIndex,
                        ],
                    );
                }
            }
        }

        for ($number = 1; $number <= 8; $number++) {
            RestaurantTable::query()->firstOrCreate(
                ['name' => 'میز '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)],
                [
                    'token' => Str::random(48),
                    'is_active' => true,
                    'description' => 'میز شماره '.$number,
                ],
            );
        }
    }
}
