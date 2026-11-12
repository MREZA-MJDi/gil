<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => RestaurantSetting::current()]);
    }

    public function update(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'currency_label' => ['sometimes', 'required', 'in:تومان,ریال'],
            'tax_basis_points' => ['sometimes', 'required', 'integer', 'min:0', 'max:10000'],
            'service_basis_points' => ['sometimes', 'required', 'integer', 'min:0', 'max:10000'],
            'receipt_footer' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'paper_width' => ['sometimes', 'required', 'integer', 'in:58,80'],
            'customer_menu_enabled' => ['sometimes', 'required', 'boolean'],
            'homepage_content' => ['sometimes', 'array'],
            'homepage_content.hero_eyebrow' => ['sometimes', 'required', 'string', 'max:100'],
            'homepage_content.hero_title_line1' => ['sometimes', 'required', 'string', 'max:80'],
            'homepage_content.hero_title_line2' => ['sometimes', 'required', 'string', 'max:80'],
            'homepage_content.hero_description' => ['sometimes', 'required', 'string', 'max:600'],
            'homepage_content.hero_primary_cta' => ['sometimes', 'required', 'string', 'max:60'],
            'homepage_content.hero_secondary_cta' => ['sometimes', 'required', 'string', 'max:60'],
            'homepage_content.hero_badge_title' => ['sometimes', 'required', 'string', 'max:80'],
            'homepage_content.hero_badge_description' => ['sometimes', 'required', 'string', 'max:120'],
            'homepage_content.showcase_eyebrow' => ['sometimes', 'required', 'string', 'max:100'],
            'homepage_content.showcase_title' => ['sometimes', 'required', 'string', 'max:100'],
            'homepage_content.showcase_description' => ['sometimes', 'required', 'string', 'max:500'],
        ]);
        if ($data === []) throw ValidationException::withMessages(['settings' => 'هیچ تغییری ارسال نشده است.']);

        $settings = RestaurantSetting::current();
        if (isset($data['currency_label']) && $data['currency_label'] !== $settings->currency_label) {
            if (Product::query()->exists() || Order::query()->exists()) {
                throw ValidationException::withMessages([
                    'currency_label' => 'برای جلوگیری از اشتباه در فاکتورهای قبلی، واحد پول را فقط قبل از ثبت محصول و سفارش انتخاب کنید؛ مبلغ‌های قبلی خودکار تبدیل نمی‌شوند.',
                ]);
            }
        }

        $old = $settings->only(array_keys($data));
        $settings->fill($data)->save();
        $audit->record('restaurant_settings.updated', $settings, $old, $settings->only(array_keys($data)), 'settings update', $request->user()->id);

        return response()->json(['data' => $settings->fresh()]);
    }
}
