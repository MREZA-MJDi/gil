<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedback';
    protected $fillable = ['order_id', 'customer_name', 'phone', 'email', 'kind', 'rating', 'message', 'status', 'admin_response', 'responded_by', 'responded_at'];
    protected function casts(): array { return ['rating' => 'integer', 'status' => FeedbackStatus::class, 'responded_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function responder(): BelongsTo { return $this->belongsTo(User::class, 'responded_by'); }
}
