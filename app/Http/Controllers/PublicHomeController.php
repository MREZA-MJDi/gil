<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantSetting;
use Illuminate\Contracts\View\View;

class PublicHomeController extends Controller
{
    public function home(): View
    {
        $settings = RestaurantSetting::current();
        $categories = $this->categories(productLimit: 4);
        $featuredProducts = $categories->flatMap(fn (Category $category) => $category->products)
            ->take(6)
            ->values();

        return view('public.home', compact('settings', 'categories', 'featuredProducts'));
    }

    public function menu(): View
    {
        $settings = RestaurantSetting::current();
        $categories = $this->categories();

        return view('public.menu', compact('settings', 'categories'));
    }

    private function categories(?int $productLimit = null)
    {
        return Category::query()
            ->active()
            ->ordered()
            ->with(['products' => function ($query) use ($productLimit) {
                $query->where('status', ProductStatus::Active->value)
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
