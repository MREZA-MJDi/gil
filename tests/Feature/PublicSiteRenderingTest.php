<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders_from_the_current_database(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('طعم خوب')
            ->assertSee(route('public.menu'));
    }


    public function test_homepage_uses_database_product_photos_for_the_hero_and_food_carousel(): void
    {
        $category = Category::query()->create([
            'name' => 'غذاهای خانه',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $first = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'غذای تصویر اول',
            'description' => 'توضیح اول',
            'image_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1000&q=85',
            'base_price' => 145000,
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $second = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'غذای تصویر دوم',
            'image_path' => 'menu/second.webp',
            'base_price' => 155000,
            'status' => 'active',
            'sort_order' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-carousel', false)
            ->assertSee('data-home-carousel-card', false)
            ->assertSee('غذای تصویر اول')
            ->assertSee('غذای تصویر دوم')
            ->assertSee(e($first->image_url), false)
            ->assertSee(e($second->image_url), false)
            ->assertSee('kg-reference-hero', false);
    }

    public function test_homepage_copy_is_read_from_dashboard_managed_settings(): void
    {
        \App\Models\RestaurantSetting::current()->forceFill([
            'homepage_content' => [
                'hero_eyebrow' => 'کافه تست',
                'hero_title_line1' => 'صبح دلنشین،',
                'hero_title_line2' => 'طعم تازه.',
                'hero_description' => 'متن ویرایش‌شده از داشبورد',
                'hero_primary_cta' => 'منوی ویژه',
                'hero_secondary_cta' => 'رزرو تازه',
                'hero_badge_title' => 'تازه و روز',
                'hero_badge_description' => 'هر روز در کنار شما',
                'showcase_eyebrow' => 'گزیده روز',
                'showcase_title' => 'انتخاب‌های تازه',
                'showcase_description' => 'این توضیح از دیتابیس خوانده می‌شود.',
            ],
        ])->save();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('صبح دلنشین،')
            ->assertSee('طعم تازه.')
            ->assertSee('متن ویرایش‌شده از داشبورد')
            ->assertSee('انتخاب‌های تازه');
    }

    public function test_homepage_category_count_includes_products_beyond_the_preview_limit(): void
    {
        $category = Category::query()->create([
            'name' => 'نوشیدنی‌های تست',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        foreach (range(1, 5) as $number) {
            Product::query()->create([
                'category_id' => $category->id,
                'name' => 'نوشیدنی تست '.$number,
                'base_price' => 1000,
                'status' => 'active',
                'sort_order' => $number,
            ]);
        }

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول ناموجود برای شمارش',
            'base_price' => 1000,
            'status' => 'active',
            'stock_quantity' => 0,
            'sort_order' => 6,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('5 محصول در منو')
            ->assertSee('نوشیدنی تست 4')
            ->assertSee('نوشیدنی تست 5');
    }

    public function test_public_menu_hides_products_with_zero_tracked_stock(): void
    {
        $category = Category::query()->create([
            'name' => 'خوراکی‌های ناموجود',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول با موجودی صفر',
            'base_price' => 1000,
            'status' => 'active',
            'stock_quantity' => 0,
        ]);

        $this->get(route('public.menu'))
            ->assertOk()
            ->assertDontSee('محصول با موجودی صفر');
    }

    public function test_public_menu_page_renders_without_seeded_demo_products(): void
    {
        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('طعم دلخواهت')
            ->assertSee('هنوز محصولی به سبد اضافه نکرده‌ای')
            ->assertSee(route('public.orders.store'));
    }


    public function test_public_menu_uses_category_selection_and_real_product_image_templates(): void
    {
        $category = Category::query()->create([
            'name' => 'غذاهای منتخب',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'غذای ویژه تست',
            'description' => 'توضیح غذای ویژه',
            'base_price' => 125000,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('data-menu-showcase', false)
            ->assertSee('data-menu-category-select="'.$category->id.'"', false)
            ->assertSee('data-menu-product-select', false)
            ->assertSee('data-menu-product-template="'.$product->id.'"', false)
            ->assertSee('data-add-online-product', false)
            ->assertSee('غذای ویژه تست');
    }

    public function test_reservation_pages_render_and_describe_pending_confirmation(): void
    {
        $this->get(route('public.reservations.create'))
            ->assertOk()
            ->assertSee('درخواست پس از بررسی ظرفیت')
            ->assertSee('name="starts_at"', false)
            ->assertSee('name="idempotency_key"', false);

        $this->get(route('public.reservations.received'))
            ->assertOk()
            ->assertSee('در انتظار تأیید');
    }
    public function test_customer_and_public_pages_use_framework_free_assets_while_dashboard_keeps_react(): void
    {
        $publicViews = [
            resource_path('views/public/home.blade.php'),
            resource_path('views/public/menu.blade.php'),
            resource_path('views/public/reserve.blade.php'),
            resource_path('views/public/reservation-received.blade.php'),
            resource_path('views/customer/menu.blade.php'),
        ];

        foreach ($publicViews as $viewPath) {
            $source = file_get_contents($viewPath);
            $this->assertIsString($source);
            $this->assertStringContainsString("resources/css/public.css", $source);
            $this->assertStringContainsString("resources/js/public-site.js", $source);
            $this->assertStringNotContainsString("resources/js/app.jsx", $source);
            $this->assertStringNotContainsString("resources/js/app.js", $source);
        }

        $dashboard = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertIsString($dashboard);
        $this->assertStringContainsString("resources/js/app.jsx", $dashboard);
        $this->assertStringContainsString("resources/css/app.css", $dashboard);

        $publicEntry = file_get_contents(resource_path('js/public-site.js'));
        $this->assertIsString($publicEntry);
        $this->assertStringNotContainsString("react", strtolower($publicEntry));
        $this->assertStringContainsString("./public/customer-qr-menu.js", $publicEntry);
    }

    public function test_public_design_system_uses_local_estedad_and_responsive_type_scale(): void
    {
        $fonts = file_get_contents(resource_path('css/fonts.css'));
        $base = file_get_contents(resource_path('css/public-base.css'));
        $entry = file_get_contents(resource_path('css/public.css'));

        $this->assertIsString($fonts);
        $this->assertIsString($base);
        $this->assertIsString($entry);
        $this->assertStringContainsString("font-family: 'Estedad'", $fonts);
        $this->assertStringContainsString("--kg-fs-display", $base);
        $this->assertStringContainsString("--kg-section-space", $base);
        $this->assertStringContainsString("public-base.css", $entry);
        $this->assertStringNotContainsString("dashboard.css", $entry);
    }

}
