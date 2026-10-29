<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = ['payment_id', 'order_id', 'user_id', 'amount', 'method', 'reason', 'idempotency_key', 'idempotency_hash', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'method' => PaymentMethod::class, 'refunded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
