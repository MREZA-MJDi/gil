<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(string $event, ?Model $model = null, array $old = [], array $new = [], ?string $reason = null, ?int $userId = null): AuditLog
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        foreach (['password', 'remember_token', 'token', 'idempotency_key'] as $sensitive) {
            unset($old[$sensitive], $new[$sensitive]);
        }

        return AuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
