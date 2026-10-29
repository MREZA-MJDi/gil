<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function adjust(
        int $productId,
        string $direction,
        int $quantity,
        string $reason,
        string $key,
        ?int $userId = null,
    ): InventoryMovement {
        $delta = $direction === 'in' ? $quantity : -$quantity;
        $hash = hash('sha256', json_encode([
            'product_id' => $productId,
            'delta' => $delta,
            'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($productId, $delta, $quantity, $direction, $reason, $key, $userId, $hash) {
            $existing = InventoryMovement::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                $existingHash = hash('sha256', json_encode([
                    'product_id' => $existing->product_id,
                    'delta' => $existing->quantity_delta,
                    'reason' => trim((string) $existing->reason),
                ], JSON_THROW_ON_ERROR));
                if (! hash_equals($existingHash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید قبلاً برای اصلاح موجودی دیگری استفاده شده است.']);
                }
                return $existing->load(['product:id,name,stock_quantity', 'user:id,name']);
            }

            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            if ($product->stock_quantity === null) {
                throw ValidationException::withMessages([
                    'product_id' => 'برای این محصول موجودی رهگیری نمی‌شود. ابتدا مقدار اولیه موجودی را در مشخصات محصول ثبت کنید.',
                ]);
            }

            $before = (int) $product->stock_quantity;
            $after = $before + $delta;
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => 'موجودی کافی نیست؛ این اصلاح باعث موجودی منفی می‌شود.']);
            }

            $product->forceFill(['stock_quantity' => $after])->save();
            $movement = InventoryMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'movement_type' => 'manual',
                'quantity_delta' => $delta,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => trim($reason),
                'idempotency_key' => $key,
            ]);
            $this->audit->record('inventory.adjusted', $movement, [
                'stock_quantity' => $before,
            ], [
                'product_id' => $product->id,
                'stock_quantity' => $after,
                'quantity_delta' => $delta,
            ], trim($reason), $userId);

            return $movement->load(['product:id,name,stock_quantity', 'user:id,name']);
        }, attempts: 3);
    }
}
