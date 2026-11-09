<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $appends = ['image_url'];

    protected $fillable = [
        'category_id', 'name', 'description', 'ingredients', 'allergens', 'dietary_tags', 'calories', 'preparation_time_minutes', 'image_path', 'base_price', 'unit',
        'status', 'sort_order', 'stock_quantity', 'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'status' => ProductStatus::class,
            'sort_order' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'allergens' => 'array',
            'dietary_tags' => 'array',
            'calories' => 'integer',
            'preparation_time_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recipeIngredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_recipe_ingredients')
            ->withPivot('quantity')->withTimestamps();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeOnMenu(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereNotNull('stock_quantity')
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;
        if (! $path) return null;

        // Demo catalog photos may be served by a trusted image CDN; local uploads remain storage paths.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $host = parse_url($path, PHP_URL_HOST);
            return in_array($host, ['images.unsplash.com', 'images.pexels.com'], true) ? $path : null;
        }

        return asset('storage/'.$path);
    }
}
