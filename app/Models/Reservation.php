<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = ['reference', 'customer_name', 'phone', 'email', 'party_size', 'starts_at', 'status', 'source', 'notes', 'idempotency_key', 'created_by'];
    protected function casts(): array { return ['party_size' => 'integer', 'starts_at' => 'datetime', 'status' => ReservationStatus::class]; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
