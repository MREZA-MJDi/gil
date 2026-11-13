<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    protected $fillable = [
        'opened_by', 'closed_by', 'cashier_name', 'status', 'open_lock',
        'opening_cash', 'expected_cash', 'counted_cash', 'difference',
        'opening_note', 'closing_note', 'opened_at', 'closed_at',
        'duration_minutes', 'orders_count', 'cancelled_orders_count', 'gross_sales', 'payments_total',
        'cash_payments_total', 'card_payments_total', 'other_payments_total',
        'refunds_total', 'net_collections_total',
    ];

    protected function casts(): array
    {
        return [
            'open_lock' => 'boolean',
            'opening_cash' => 'integer',
            'expected_cash' => 'integer',
            'counted_cash' => 'integer',
            'difference' => 'integer',
            'duration_minutes' => 'integer',
            'orders_count' => 'integer',
            'cancelled_orders_count' => 'integer',
            'gross_sales' => 'integer',
            'payments_total' => 'integer',
            'cash_payments_total' => 'integer',
            'card_payments_total' => 'integer',
            'other_payments_total' => 'integer',
            'refunds_total' => 'integer',
            'net_collections_total' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function movements(): HasMany { return $this->hasMany(CashMovement::class)->orderBy('occurred_at')->orderBy('id'); }
}
