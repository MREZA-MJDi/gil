<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['cash_shift_id', 'user_id', 'title', 'category', 'amount', 'payment_method', 'is_paid', 'paid_at', 'receipt_path', 'note'];
    protected function casts(): array { return ['amount' => 'integer', 'is_paid' => 'boolean', 'paid_at' => 'datetime']; }
    public function shift(): BelongsTo { return $this->belongsTo(CashShift::class, 'cash_shift_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
