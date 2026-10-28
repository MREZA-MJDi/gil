<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'amount', 'method', 'status', 'provider',
        'reference', 'note', 'idempotency_key', 'idempotency_hash', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'status' => TransactionStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class)->orderBy('refunded_at')->orderBy('id'); }

    public function refundable(): int
    {
        return max(0, $this->amount - (int) $this->refunds()->sum('amount'));
    }
}
