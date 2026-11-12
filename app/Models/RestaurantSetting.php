<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'singleton_key', 'name', 'phone', 'address', 'logo_path',
        'currency_label', 'tax_basis_points', 'service_basis_points',
        'receipt_footer', 'paper_width', 'customer_menu_enabled',
    ];

    protected function casts(): array
    {
        return [
            'tax_basis_points' => 'integer',
            'service_basis_points' => 'integer',
            'paper_width' => 'integer',
            'customer_menu_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        // Uniqueness is guaranteed by restaurant_settings.singleton_key, not by cache.
        return static::query()->firstOrCreate(['singleton_key' => 'main'], [
            'name' => 'خانه گیلاسی', 'currency_label' => config('restaurant.currency_label', 'تومان'), 'paper_width' => 80,
        ]);
    }
}
