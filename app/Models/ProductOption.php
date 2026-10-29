<?php

namespace App\Models;

use App\Enums\OptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $fillable = ['product_id', 'name', 'type', 'price', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => OptionType::class,
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
