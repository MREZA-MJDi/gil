<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'reference', 'restaurant_table_id', 'customer_name', 'phone', 'email', 'party_size',
        'starts_at', 'duration_minutes', 'ends_at', 'status', 'source', 'purpose', 'occasion_name', 'estimated_budget', 'notes', 'idempotency_key', 'idempotency_hash', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'estimated_budget' => 'integer',
            'duration_minutes' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    public function table(): BelongsTo { return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
