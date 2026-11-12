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

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('5 محصول فعال')
            ->assertSee('نوشیدنی تست 4')
            ->assertDontSee('نوشیدنی تست 5');
    }

    public function test_public_menu_page_renders_without_seeded_demo_products(): void
    {
        $this->get(route('public.menu'))
            ->assertOk()
            ->assertSee('منو برای')
            ->assertSee('هنوز محصولی به سبد اضافه نکرده‌ای')
            ->assertSee(route('public.orders.store'));
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
}
