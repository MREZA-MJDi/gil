<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'number', 'restaurant_table_id', 'channel', 'source', 'external_provider', 'external_order_id', 'payment_preference', 'status', 'payment_status',
        'subtotal', 'discount', 'tax', 'service', 'total', 'customer_name',
        'customer_phone', 'customer_note', 'internal_note', 'idempotency_key',
        'idempotency_hash', 'cancel_reason', 'cancelled_at', 'status_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer', 'discount' => 'integer', 'tax' => 'integer',
            'service' => 'integer', 'total' => 'integer',
            'cancelled_at' => 'datetime', 'status_changed_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo { return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id'); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class)->orderBy('id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class)->orderBy('paid_at')->orderBy('id'); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class)->orderBy('refunded_at')->orderBy('id'); }
    public function statusHistory(): HasMany { return $this->hasMany(OrderStatusHistory::class)->orderBy('id'); }
    public function printLogs(): HasMany { return $this->hasMany(PrintLog::class)->orderBy('id'); }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled->value);
    }

    public function scopeUnsettled(Builder $query): Builder
    {
        return $query->whereIn('payment_status', [
            PaymentStatus::Unpaid->value,
            PaymentStatus::Partial->value,
            PaymentStatus::PartiallyRefunded->value,
        ]);
    }

    public function paidTotal(): int
    {
        return (int) $this->payments()->where('status', 'succeeded')->sum('amount');
    }

    public function refundedTotal(): int
    {
        return (int) $this->refunds()->sum('amount');
    }

    public function netPaid(): int
    {
        return $this->paidTotal() - $this->refundedTotal();
    }

    public function remaining(): int
    {
        return max(0, $this->total - $this->netPaid());
    }
}
