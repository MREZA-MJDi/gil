<?php

namespace Tests\Feature;

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
                ->assertSee('public-site', false);
        }
    }
}
