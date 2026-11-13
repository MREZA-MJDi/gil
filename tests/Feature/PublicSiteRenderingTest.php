<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use Illuminate\Support\Str;
use App\Models\SiteMediaAsset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders_from_the_current_database(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('پیشنهادهای')
            ->assertDontSee('طعم خوب')
            ->assertDontSee('حال خوب')
            ->assertDontSee('خوش‌آمدی به')
            ->assertDontSee('برای یک قهوه کوتاه')
            ->assertSee('class="logo"', false)
            ->assertSee('home-hero-signature', false)
            ->assertSee('یک مکث خوش‌طعم.')
            ->assertSee(route('public.menu'))
            ->assertSee('data-best-food-carousel', false);
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
            ->assertSee('data-best-food-slide', false)
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
            ->assertDontSee('صبح دلنشین،')
            ->assertDontSee('طعم تازه.')
            ->assertDontSee('متن ویرایش‌شده از داشبورد')
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


    public function test_dashboard_media_assets_are_rendered_on_home_menu_navigation_and_reservation_pages(): void
    {
        Storage::fake('public');
        $assets = [
            'hero' => 'site-media/hero.webp',
            'brand_logo' => 'site-media/logo.png',
            'home_reservation_banner' => 'site-media/home-reservation.webp',
            'gallery_1' => 'site-media/gallery-one.webp',
            'menu_cover' => 'site-media/menu-cover.webp',
            'reservation_cover' => 'site-media/reservation-cover.webp',
        ];

        foreach ($assets as $slot => $path) {
            Storage::disk('public')->put($path, 'test-image');
            SiteMediaAsset::query()->create([
                'slot' => $slot,
                'path' => $path,
                'alt_text' => 'Managed '.$slot,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('site-media/hero.webp')
            ->assertSee('site-media/logo.png')
            ->assertSee('site-media/home-reservation.webp')
            ->assertSee('site-media/gallery-one.webp')
            ->assertSee('kg-photo-gallery', false)
            ->assertSee('data-best-food-carousel', false);

        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('site-media/menu-cover.webp')
            ->assertSee('kg-menu-cover', false);

        $customer = User::factory()->create(['phone' => '+989121234567', 'email' => null, 'is_admin' => false]);
        $this->actingAs($customer)->get(route('public.reservations.create'))
            ->assertOk()
            ->assertSee('site-media/reservation-cover.webp');
    }

    public function test_dashboard_logo_asset_uses_a_compact_round_brand_mark(): void
    {
        SiteMediaAsset::query()->create([
            'slot' => 'brand_logo',
            'path' => 'site-media/brand-logo.png',
            'alt_text' => 'نشان خانه گیلاسی',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="logo"', false)
            ->assertSee('site-media/brand-logo.png')
            ->assertSee('نشان خانه گیلاسی');

        foreach ([
            resource_path('views/public/home.blade.php'),
            resource_path('views/public/reserve.blade.php'),
            resource_path('views/public/reservation-received.blade.php'),
            resource_path('views/customer/auth/login.blade.php'),
            resource_path('views/customer/auth/register.blade.php'),
        ] as $viewPath) {
            $source = file_get_contents($viewPath);
            $this->assertIsString($source);
            $this->assertStringContainsString("public.partials.site-logo", $source);
        }
    }

    public function test_mobile_navigation_has_inset_popovers_and_dismiss_controls(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-controls="kg-reference-nav"', false);

        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('aria-controls="kg-menu-nav"', false);

        $navigation = file_get_contents(resource_path('js/public/navigation.js'));
        $styles = file_get_contents(resource_path('css/public-reference.css'));

        $this->assertIsString($navigation);
        $this->assertIsString($styles);
        $this->assertStringContainsString("event.key === 'Escape'", $navigation);
        $this->assertStringContainsString("document.addEventListener('pointerdown'", $navigation);
        $this->assertStringContainsString('.top .menu-nav.is-open', $styles);
        $this->assertStringContainsString('calc(100vw - 32px)', $styles);
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
            ->assertSee('data-product-card', false)
            ->assertSee('value="card_terminal"', false)
            ->assertDontSee('value="online"', false)
            ->assertSee('غذای ویژه تست');
    }

    public function test_public_menu_accepts_and_persists_a_takeaway_order(): void
    {
        $category = Category::query()->create([
            'name' => 'سفارش‌های تست',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'ساندویچ تست',
            'base_price' => 125000,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->postJson(route('public.orders.store'), [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'options' => [],
            ]],
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09121234567',
            'customer_note' => 'بدون پیاز',
            'payment_preference' => 'card_terminal',
            'idempotency_key' => Str::random(32),
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.total', 250000)
            ->assertJsonStructure(['data' => ['number', 'status', 'payment_status', 'total']]);

        $this->assertDatabaseHas('orders', [
            'channel' => 'takeaway',
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09121234567',
            'payment_preference' => 'card_terminal',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'name' => 'ساندویچ تست',
            'quantity' => 2,
            'line_total' => 250000,
        ]);
    }

    public function test_reservation_pages_render_and_describe_pending_confirmation(): void
    {
        $customer = User::factory()->create(['name' => 'مشتری تست', 'phone' => '+989121234567', 'email' => null, 'is_admin' => false]);
        $this->actingAs($customer)->get(route('public.reservations.create'))
            ->assertOk()
            ->assertSee('درخواست پس از بررسی ظرفیت')
            ->assertSee('name="starts_at"', false)
            ->assertSee('name="idempotency_key"', false);

        $this->actingAs($customer)->get(route('public.reservations.received'))
            ->assertOk()
            ->assertSee('در انتظار بررسی');
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
            $this->assertStringContainsString("resources/css/public-reference.css", $source);
            $this->assertStringContainsString("resources/js/public-site.js", $source);
            $this->assertStringNotContainsString("resources/js/app.jsx", $source);
            $this->assertStringNotContainsString("resources/js/app.js", $source);
        }

        $dashboard = file_get_contents(resource_path('views/admin/dashboard.blade.php'));
        $this->assertIsString($dashboard);
        $this->assertStringContainsString("resources/js/app.jsx", $dashboard);
        $this->assertStringContainsString("resources/css/app.css", $dashboard);


        foreach ([
            resource_path('views/admin/login.blade.php'),
            resource_path('views/admin/register.blade.php'),
        ] as $authViewPath) {
            $authSource = file_get_contents($authViewPath);
            $this->assertIsString($authSource);
            $this->assertStringContainsString("resources/css/auth-entry.css", $authSource);
            $this->assertStringNotContainsString("resources/js/app.jsx", $authSource);
            $this->assertStringNotContainsString("resources/js/app.js", $authSource);
        }

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


    public function test_qr_scan_opens_the_selected_tables_menu_and_has_an_order_success_panel(): void
    {
        RestaurantSetting::current()->forceFill(['customer_menu_enabled' => true])->save();

        $table = RestaurantTable::query()->create([
            'name' => 'میز QR تست',
            'token' => Str::random(48),
            'is_active' => true,
        ]);

        $this->get($table->menuUrl())
            ->assertOk()
            ->assertSee('میز QR تست')
            ->assertSee('data-customer-qr-menu', false)
            ->assertSee(route('customer.menu.data', ['token' => $table->token]))
            ->assertSee(route('customer.menu.orders.store', ['token' => $table->token]));

        $qrScript = file_get_contents(resource_path('js/public/customer-qr-menu.js'));
        $this->assertIsString($qrScript);
        $this->assertStringContainsString('kg-qr-success', $qrScript);
        $this->assertStringContainsString('showQrSuccess', $qrScript);
        $this->assertStringContainsString('پرداخت هنوز انجام نشده', $qrScript);
    }

}
