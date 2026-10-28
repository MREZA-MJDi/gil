<?php

namespace App\Models;

use App\Enums\PrintType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['order_id', 'payment_id', 'user_id', 'type', 'document_number', 'is_reprint', 'printed_at'];

    protected function casts(): array
    {
        return ['type' => PrintType::class, 'is_reprint' => 'boolean', 'printed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
