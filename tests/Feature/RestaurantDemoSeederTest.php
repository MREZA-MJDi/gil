<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CashMovement;
use App\Models\CustomerFeedback;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\RestaurantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RestaurantDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seeder_builds_a_complete_testable_restaurant_and_can_be_repeated(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['app.env' => 'local']);
        User::query()->create([
            'name' => 'Demo Test Manager',
            'email' => 'demo-manager@example.test',
            'password' => 'demo-test-password-12345',
            'is_admin' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RestaurantDemoSeeder::class,
            '--force' => true,
        ]);

        $this->assertGreaterThanOrEqual(4, Category::query()->count());
        $this->assertGreaterThanOrEqual(15, Product::query()->count());
        $this->assertGreaterThanOrEqual(8, RestaurantTable::query()->count());
        $this->assertGreaterThan(0, Ingredient::query()->count());
        $this->assertGreaterThan(0, \DB::table('product_recipe_ingredients')->count());
        $this->assertGreaterThanOrEqual(5, Order::query()->count());
        $this->assertTrue(Order::query()->where('source', 'qr')->exists());
        $this->assertTrue(Order::query()->where('source', 'website')->exists());
        $this->assertTrue(Order::query()->where('source', 'snappfood')->exists());
        $this->assertTrue(Order::query()->whereNotNull('external_order_id')->exists());
        $this->assertTrue(Reservation::query()->where('purpose', 'birthday')->exists());
        $this->assertTrue(Reservation::query()->where('purpose', 'consultation')->exists());
        $this->assertGreaterThan(0, Expense::query()->count());
        $this->assertTrue(Expense::query()->where('seed_key', 'demo-expense:ingredients-weekly')->exists());
        $this->assertGreaterThan(0, CustomerFeedback::query()->count());
        $this->assertTrue(CashMovement::query()->where('type', 'expense')->exists());

        $photo = Product::query()->whereNotNull('image_path')->firstOrFail()->image_url;
        $this->assertStringStartsWith('https://images.unsplash.com/', (string) $photo);

        $orderCount = Order::query()->count();
        $stockBefore = Ingredient::query()->orderBy('id')->firstOrFail()->stock_quantity;

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RestaurantDemoSeeder::class,
            '--force' => true,
        ]);

        $this->assertSame($orderCount, Order::query()->count(), 'Running the demo seeder twice must not duplicate orders.');
        $this->assertSame($stockBefore, Ingredient::query()->orderBy('id')->firstOrFail()->stock_quantity);
    }


    public function test_reseeding_preserves_dashboard_edits_to_catalog_ingredients_tables_and_settings(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['app.env' => 'local']);
        User::query()->create([
            'name' => 'Editable Demo Manager',
            'email' => 'editable-demo@example.test',
            'password' => 'demo-test-password-12345',
            'is_admin' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RestaurantDemoSeeder::class,
            '--force' => true,
        ]);

        $product = Product::query()->where('seed_key', 'demo-product:latte')->firstOrFail();
        $category = Category::query()->where('seed_key', 'demo-category:coffee')->firstOrFail();
        $ingredient = Ingredient::query()->where('seed_key', 'demo-ingredient:coffee_beans')->firstOrFail();
        $table = RestaurantTable::query()->where('seed_key', 'demo-table:1')->firstOrFail();

        $product->update(['name' => 'لاته ویرایش‌شده', 'base_price' => 199000]);
        $category->update(['name' => 'قهوه‌های منتخب']);
        $ingredient->update(['name' => 'دانه قهوه ویژه', 'stock_quantity' => 321, 'cost_per_unit' => 987]);
        $table->update(['name' => 'میز پنجره', 'description' => 'تغییر داده شده از پنل']);
        RestaurantSetting::current()->update(['name' => 'مجموعه آزمایشی ویرایش‌شده']);

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RestaurantDemoSeeder::class,
            '--force' => true,
        ]);

        $this->assertSame('لاته ویرایش‌شده', $product->fresh()->name);
        $this->assertSame(199000, (int) $product->fresh()->base_price);
        $this->assertSame('قهوه‌های منتخب', $category->fresh()->name);
        $this->assertSame('دانه قهوه ویژه', $ingredient->fresh()->name);
        $this->assertSame(321.0, (float) $ingredient->fresh()->stock_quantity);
        $this->assertSame(987, (int) $ingredient->fresh()->cost_per_unit);
        $this->assertSame('میز پنجره', $table->fresh()->name);
        $this->assertSame('تغییر داده شده از پنل', $table->fresh()->description);
        $this->assertSame('مجموعه آزمایشی ویرایش‌شده', RestaurantSetting::current()->name);
    }

    public function test_demo_seeder_respects_rial_as_the_chosen_currency_unit(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['app.env' => 'local', 'restaurant.currency_label' => 'ریال']);
        User::query()->create([
            'name' => 'Rial Demo Manager',
            'email' => 'rial-demo@example.test',
            'password' => 'demo-test-password-12345',
            'is_admin' => true,
        ]);

        $settings = RestaurantSetting::current();
        $this->assertSame('ریال', $settings->currency_label);

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RestaurantDemoSeeder::class,
            '--force' => true,
        ]);

        $this->assertSame('ریال', RestaurantSetting::current()->currency_label);
        $this->assertSame(1450000, (int) Product::query()->where('name', 'لاته کلاسیک')->value('base_price'));
        $this->assertSame(4000, (int) Ingredient::query()->where('name', 'دانه قهوه')->value('cost_per_unit'));
        $this->assertSame(18500000, (int) Expense::query()->where('title', 'DEMO · خرید مواد اولیه هفتگی')->value('amount'));
        $this->assertSame(80000000, (int) Reservation::query()->where('reference', 'DEMO-RSV-BDAY')->value('estimated_budget'));
    }


    public function test_production_demo_seeder_requires_an_explicit_one_command_opt_in(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.env' => 'production']);

        $previous = getenv('ALLOW_PRODUCTION_DEMO_SEED');
        putenv('ALLOW_PRODUCTION_DEMO_SEED=false');

        try {
            $this->expectException(\RuntimeException::class);
            app(\Database\Seeders\RestaurantDemoSeeder::class)->run();
        } finally {
            if ($previous === false) {
                putenv('ALLOW_PRODUCTION_DEMO_SEED');
            } else {
                putenv('ALLOW_PRODUCTION_DEMO_SEED='.$previous);
            }
        }
    }

    public function test_demo_seeder_refuses_to_run_outside_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');
        config(['app.env' => 'testing']);

        $this->expectException(\RuntimeException::class);
        app(\Database\Seeders\RestaurantDemoSeeder::class)->run();
    }
}
