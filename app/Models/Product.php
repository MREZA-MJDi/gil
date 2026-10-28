<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'description', 'image_path', 'base_price', 'unit',
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
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
