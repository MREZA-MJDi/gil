<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name', 'unit', 'stock_quantity', 'low_stock_threshold', 'cost_per_unit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'cost_per_unit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_recipe_ingredients')
            ->withPivot('quantity')->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(IngredientInventoryMovement::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }
}
