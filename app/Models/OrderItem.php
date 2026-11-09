<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'name', 'unit_price', 'quantity',
        'options', 'recipe_snapshot', 'options_total', 'line_total', 'note',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer', 'quantity' => 'integer',
            'options' => 'array', 'recipe_snapshot' => 'array', 'options_total' => 'integer', 'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
