<?php

namespace Tests\Feature;

use App\Models\SiteMediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_menu_and_reservation_pages_render_rtl_blade_views(): void
    {
        foreach (['/', '/menu', '/reserve', '/reserve/received'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('dir="rtl"', false)
                ->assertSee('public-site', false)
                ->assertSee('aria-controls="public-primary-navigation"', false)
                ->assertSee('id="public-primary-navigation"', false);
        }
    }

    public function test_home_gallery_uses_all_three_managed_media_slots_and_alt_text(): void
    {
        $assets = [
            ['slot' => 'gallery_1', 'path' => 'site-media/gallery_1/space.webp', 'alt_text' => 'فضای داخلی ثبت‌شده'],
            ['slot' => 'gallery_2', 'path' => 'site-media/gallery_2/food.webp', 'alt_text' => 'غذای ثبت‌شده'],
            ['slot' => 'gallery_3', 'path' => 'site-media/gallery_3/event.webp', 'alt_text' => 'چیدمان ثبت‌شده'],
        ];

        foreach ($assets as $asset) {
            SiteMediaAsset::query()->create($asset);
        }

        $response = $this->get('/')->assertOk();

        foreach ($assets as $asset) {
            $response
                ->assertSee($asset['path'], false)
                ->assertSee('alt="'.$asset['alt_text'].'"', false);
        }
    }
}
