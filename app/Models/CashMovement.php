<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'cash_shift_id', 'user_id', 'order_id', 'payment_id', 'refund_id', 'expense_id',
        'direction', 'type', 'amount', 'reason', 'idempotency_key', 'occurred_at',
    ];

    protected function casts(): array { return ['amount' => 'integer', 'occurred_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function shift(): BelongsTo { return $this->belongsTo(CashShift::class, 'cash_shift_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function refund(): BelongsTo { return $this->belongsTo(Refund::class); }
    public function expense(): BelongsTo { return $this->belongsTo(Expense::class); }
}
