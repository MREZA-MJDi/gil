<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientInventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ingredient_id', 'order_id', 'user_id', 'movement_type', 'quantity_delta',
        'stock_before', 'stock_after', 'reason', 'idempotency_key', 'idempotency_hash', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
