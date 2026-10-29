<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestaurantTableService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data, ?int $userId = null): RestaurantTable
    {
        return DB::transaction(function () use ($data, $userId) {
            $table = RestaurantTable::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'token' => Str::random(64),
                'is_active' => true,
            ]);
            $this->audit->record('table.created', $table, [], [
                'name' => $table->name, 'is_active' => true,
            ], null, $userId);
            return $table;
        }, attempts: 3);
    }

    public function update(int $tableId, array $data, ?int $userId = null): RestaurantTable
    {
        return DB::transaction(function () use ($tableId, $data, $userId) {
            $table = RestaurantTable::query()->whereKey($tableId)->lockForUpdate()->firstOrFail();
            if (array_key_exists('is_active', $data) && $data['is_active'] === false) {
                $hasOpenOrders = Order::query()
                    ->where('restaurant_table_id', $table->id)
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->exists();

                if ($hasOpenOrders) {
                    throw ValidationException::withMessages([
                        'is_active' => 'میزی که سفارش باز دارد قابل غیرفعال‌سازی نیست.',
                    ]);
                }
            }

            $old = $table->only(array_keys($data));
            $table->fill($data)->save();
            $this->audit->record('table.updated', $table, $old, $table->only(array_keys($data)), null, $userId);
            return $table;
        }, attempts: 3);
    }

    public function rotateToken(int $tableId, ?int $userId = null): RestaurantTable
    {
        return DB::transaction(function () use ($tableId, $userId) {
            $table = RestaurantTable::query()->whereKey($tableId)->lockForUpdate()->firstOrFail();
            $old = ['token_fingerprint' => hash('sha256', $table->token)];
            $table->forceFill(['token' => Str::random(64)])->save();
            $this->audit->record('table.qr_rotated', $table, $old, [
                'token_fingerprint' => hash('sha256', $table->token),
            ], 'QR token rotation', $userId);
            return $table;
        }, attempts: 3);
    }
}
