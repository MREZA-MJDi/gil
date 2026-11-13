<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantSetting;
use App\Models\SiteMediaAsset;
use Illuminate\Contracts\View\View;

class PublicHomeController extends Controller
{
    public function home(): View
    {
        $settings = RestaurantSetting::current();
        $categories = $this->categories(productLimit: 4);

        $featuredProducts = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->whereHas('category', fn ($category) => $category->where('is_active', true))
            ->where(fn ($stock) => $stock->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(7)
            ->get();

        $media = $this->media();
        $homepageContent = array_replace([
            'hero_eyebrow' => 'کافه و رستوران خانه گیلاسی',
            'hero_title_line1' => 'خوش‌آمدی به',
            'hero_title_line2' => $settings->name,
            'hero_description' => 'برای یک قهوه کوتاه، یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی؛ انتخاب و سفارش را ساده کرده‌ایم تا وقتت برای لذت‌بردن بماند.',
            'hero_primary_cta' => 'دیدن منو و سفارش',
            'hero_secondary_cta' => 'درخواست رزرو',
            'hero_badge_title' => 'با عشق پخته‌شده',
            'hero_badge_description' => 'از آشپزخانه تا میز تو',
            'showcase_eyebrow' => 'انتخاب‌های خانه گیلاسی',
            'showcase_title' => 'بهترین طعم‌ها برای تو',
            'showcase_description' => 'محصولات فعال و تصاویر ثبت‌شده در منوی مجموعه؛ برای دیدن جزئیات و سفارش، انتخابشان کن.',
        ], $settings->homepage_content ?? []);

        return view('public.home', compact('settings', 'categories', 'featuredProducts', 'media', 'homepageContent'));
    }

    public function menu(): View
    {
        $settings = RestaurantSetting::current();
        $categories = $this->categories();
        $media = $this->media();

        return view('public.menu', compact('settings', 'categories', 'media'));
    }

    private function media(): array
    {
        return SiteMediaAsset::query()->get()->mapWithKeys(fn (SiteMediaAsset $asset) => [
            $asset->slot => [
                'url' => $asset->url,
                'alt_text' => $asset->alt_text,
            ],
        ])->all();
    }

    private function categories(?int $productLimit = null)
    {
        return Category::query()
            ->active()
            ->ordered()
            ->withCount(['products as available_products_count' => fn ($query) => $query
                ->where('status', ProductStatus::Active->value)
                ->where(fn ($stock) => $stock->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))])
            ->with(['products' => function ($query) use ($productLimit) {
                $query->where('status', ProductStatus::Active->value)
                    ->where(fn ($stock) => $stock->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with(['options' => fn ($options) => $options->where('is_active', true)->orderBy('sort_order')]);

                if ($productLimit !== null) {
                    $query->limit($productLimit);
                }
            }])
            ->get()
            ->filter(fn (Category $category) => $category->products->isNotEmpty())
            ->values();
    }
}
