<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Category;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use App\Models\SiteMediaAsset;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function show(string $token): View
    {
        $table = $this->findTable($token);
        abort_unless(RestaurantSetting::current()->customer_menu_enabled, 404);

        return view('customer.menu', [
            'table' => $table,
            'settings' => RestaurantSetting::current(),
        ]);
    }

    public function data(string $token): JsonResponse
    {
        $table = $this->findTable($token);
        abort_unless(RestaurantSetting::current()->customer_menu_enabled, 404);

        $categories = Category::query()->active()->ordered()->with([
            'products' => fn ($query) => $query
                ->where('status', ProductStatus::Active->value)
                ->where(fn ($stock) => $stock->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))
                ->with(['options' => fn ($options) => $options->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')->orderBy('name'),
        ])->get();

        $settings = RestaurantSetting::current();

        // Keep this public QR payload explicit. Returning Eloquent models directly
        // would also expose operational fields such as stock_quantity and
        // low_stock_threshold to anyone holding a table QR token.
        $publicCategories = $categories->map(fn (Category $category) => [
            'id' => (int) $category->id,
            'name' => $category->name,
            'products' => $category->products->map(fn ($product) => [
                'id' => (int) $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'image_url' => $product->image_url,
                'base_price' => (int) $product->base_price,
                'unit' => $product->unit,
                'ingredients' => $product->ingredients,
                'allergens' => $product->allergens,
                'dietary_tags' => $product->dietary_tags,
                'calories' => $product->calories,
                'preparation_time_minutes' => $product->preparation_time_minutes,
                'options' => $product->options->map(fn ($option) => [
                    'id' => (int) $option->id,
                    'name' => $option->name,
                    'type' => $option->type->value,
                    'price' => (int) $option->price,
                ])->values(),
            ])->values(),
        ])->values();

        return response()->json(['data' => [
            'table' => ['id' => $table->id, 'name' => $table->name],
            'settings' => [
                'name' => $settings->name,
                'phone' => $settings->phone,
                'address' => $settings->address,
                'logo_url' => SiteMediaAsset::urlFor('brand_logo', $settings->logo_path ? asset('storage/'.$settings->logo_path) : null),
                'menu_cover_url' => SiteMediaAsset::urlFor('menu_cover'),
                'currency_label' => $settings->currency_label,
                'tax_basis_points' => $settings->tax_basis_points,
                'service_basis_points' => $settings->service_basis_points,
            ],
            'categories' => $publicCategories,
        ]]);
    }

    public function placeOrder(StoreOrderRequest $request, string $token, OrderService $orders): JsonResponse
    {
        $table = $this->findTable($token);
        abort_unless(RestaurantSetting::current()->customer_menu_enabled, 404);

        $data = $request->validated();
        if (isset($data['restaurant_table_id']) && (int) $data['restaurant_table_id'] !== $table->id) {
            throw ValidationException::withMessages(['restaurant_table_id' => 'میز با QR مطابقت ندارد.']);
        }
        if ($data['channel'] !== 'dine_in') {
            throw ValidationException::withMessages(['channel' => 'از طریق QR فقط سفارش حضوری میز ثبت می‌شود.']);
        }

        $data['restaurant_table_id'] = $table->id;
        $data['channel'] = 'dine_in';
        $data['source'] = 'qr';
        $data['customer_id'] = $request->user() && ! $request->user()->is_admin
            ? (int) $request->user()->id
            : null;

        return response()->json([
            'data' => tap($orders->create($data, null), fn ($order) => $order->load('items'))
                ->only(['number', 'status', 'total', 'created_at']),
        ], 201);
    }

    private function findTable(string $token): RestaurantTable
    {
        abort_if(strlen($token) < 40 || strlen($token) > 96, 404);

        return RestaurantTable::query()->active()->where('token', $token)->firstOrFail();
    }
}
