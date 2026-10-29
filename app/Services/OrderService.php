<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly AuditLogger $audit, private readonly RecipeInventoryService $recipeInventory) {}

    public function create(array $input, ?int $userId = null): Order
    {
        $canonical = $this->canonicalize($input);
        $hash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
        $key = (string) $input['idempotency_key'];

        return DB::transaction(function () use ($input, $userId, $key, $hash) {
            $existing = Order::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید قبلاً با درخواست دیگری استفاده شده است.']);
                }

                return $existing->load(['items', 'table', 'payments']);
            }

            $table = null;
            if (! empty($input['restaurant_table_id'])) {
                $table = RestaurantTable::query()->whereKey($input['restaurant_table_id'])->lockForUpdate()->first();
                if (! $table || ! $table->is_active) {
                    throw ValidationException::withMessages(['restaurant_table_id' => 'میز انتخاب‌شده فعال نیست.']);
                }
            }

            $settings = RestaurantSetting::current();
            $productIds = collect($input['items'])->pluck('product_id')->map(fn ($v) => (int) $v)->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'یک یا چند محصول معتبر نیست.']);
            }

            $optionIds = collect($input['items'])
                ->flatMap(fn ($item) => collect($item['options'] ?? [])->pluck('id'))
                ->map(fn ($v) => (int) $v)->unique()->sort()->values();
            $options = $optionIds->isEmpty() ? collect() : ProductOption::query()
                ->whereIn('id', $optionIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $requestedQuantities = collect($input['items'])
                ->groupBy(fn (array $item) => (int) $item['product_id'])
                ->map(fn ($items) => (int) $items->sum(fn (array $item) => (int) $item['quantity']));

            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products->get((int) $productId);
                if ($product->stock_quantity !== null && (int) $product->stock_quantity < (int) $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "موجودی «{$product->name}» کافی نیست؛ موجودی فعلی {$product->stock_quantity} عدد است.",
                    ]);
                }
            }

            $lines = [];
            $subtotal = 0;
            foreach ($input['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                if ($product->status !== ProductStatus::Active || ! $product->category()->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['items' => "محصول «{$product->name}» در حال حاضر قابل سفارش نیست."]);
                }

                $quantity = (int) $item['quantity'];
                $selected = [];
                $optionsUnitTotal = 0;
                foreach ($item['options'] ?? [] as $optionInput) {
                    $option = $options->get((int) $optionInput['id']);
                    if (! $option || (int) $option->product_id !== (int) $product->id || ! $option->is_active) {
                        throw ValidationException::withMessages(['items' => 'افزودنی انتخاب‌شده معتبر نیست.']);
                    }
                    $selected[] = [
                        'id' => $option->id,
                        'name' => $option->name,
                        'type' => $option->type instanceof \BackedEnum ? $option->type->value : $option->type,
                        'price' => (int) $option->price,
                    ];
                    $optionsUnitTotal += (int) $option->price;
                }

                $unitPrice = (int) $product->base_price;
                $lineTotal = ($unitPrice + $optionsUnitTotal) * $quantity;
                if ($lineTotal > PHP_INT_MAX - $subtotal) {
                    throw ValidationException::withMessages(['items' => 'مجموع سفارش بیش از محدوده مجاز است.']);
                }
                $subtotal += $lineTotal;
                $lines[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'options' => $selected,
                    'options_total' => $optionsUnitTotal,
                    'line_total' => $lineTotal,
                    'note' => $item['note'] ?? null,
                ];
            }

            $discount = (int) ($input['discount'] ?? 0);
            if ($discount < 0 || $discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'تخفیف باید بین صفر و مبلغ اقلام باشد.']);
            }

            $taxable = $subtotal - $discount;
            $tax = intdiv(($taxable * (int) $settings->tax_basis_points) + 5000, 10000);
            $service = intdiv(($taxable * (int) $settings->service_basis_points) + 5000, 10000);
            $total = $taxable + $tax + $service;
            if ($total < 0 || $total > PHP_INT_MAX) {
                throw ValidationException::withMessages(['items' => 'مبلغ نهایی معتبر نیست.']);
            }

            $order = Order::query()->create([
                'number' => 'KG-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'restaurant_table_id' => $table?->id,
                'channel' => $input['channel'],
                'source' => $input['source'] ?? 'staff',
                'external_provider' => $input['external_provider'] ?? null,
                'external_order_id' => $input['external_order_id'] ?? null,
                'status' => OrderStatus::New,
                'payment_status' => PaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'service' => $service,
                'total' => $total,
                'customer_name' => $input['customer_name'] ?? null,
                'customer_phone' => $input['customer_phone'] ?? null,
                'customer_note' => $input['customer_note'] ?? null,
                'internal_note' => $input['internal_note'] ?? null,
                'idempotency_key' => $key,
                'idempotency_hash' => $hash,
                'status_changed_at' => now(),
            ]);

            $order->items()->createMany($lines);
            $this->recipeInventory->consumeForOrder($order, $requestedQuantities->all(), $userId);
            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products->get((int) $productId);
                // A null stock quantity means stock tracking is intentionally disabled for this product.
                if ($product->stock_quantity === null) continue;

                $before = (int) $product->stock_quantity;
                $after = $before - (int) $quantity;
                $product->forceFill(['stock_quantity' => $after])->save();

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'movement_type' => 'order',
                    'quantity_delta' => -(int) $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => 'مصرف موجودی برای سفارش '.$order->number,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'from_status' => null,
                'to_status' => OrderStatus::New->value,
                'reason' => 'order_created',
            ]);
            $this->audit->record('order.created', $order, [], [
                'number' => $order->number, 'total' => $total, 'channel' => $order->channel,
            ], null, $userId);

            return $order->load(['items', 'table', 'payments']);
        }, attempts: 3);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    public function transition(int $orderId, OrderStatus $next, ?string $reason = null, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($orderId, $next, $reason, $userId) {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $current = $order->status;
            if (! in_array($next, $current->next(), true)) {
                throw ValidationException::withMessages(['status' => 'تغییر وضعیت سفارش از این مرحله مجاز نیست.']);
            }

            $order->forceFill(['status' => $next, 'status_changed_at' => now()])->save();
            OrderStatusHistory::query()->create([
                'order_id' => $order->id, 'user_id' => $userId,
                'from_status' => $current->value, 'to_status' => $next->value, 'reason' => $reason,
            ]);
            $this->audit->record('order.status_changed', $order, ['status' => $current->value], ['status' => $next->value], $reason, $userId);

            return $order->load(['items', 'table', 'payments']);
        }, attempts: 3);
    }

    public function cancel(int $orderId, string $reason, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($orderId, $reason, $userId) {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['order' => 'این سفارش قابل لغو عادی نیست.']);
            }
            if ($order->netPaid() > 0) {
                throw ValidationException::withMessages(['order' => 'سفارش دارای پرداخت باید از مسیر بازپرداخت اصلاح شود.']);
            }

            $items = $order->items()->get(['id', 'product_id', 'quantity', 'recipe_snapshot']);
            $trackedProductIds = $items->pluck('product_id')->filter()->unique()->values();
            $trackedProducts = $trackedProductIds->isEmpty()
                ? collect()
                : Product::query()->whereIn('id', $trackedProductIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $this->recipeInventory->restoreForCancelledOrder($order->setRelation('items', $items), $userId);

            foreach ($items->groupBy('product_id') as $productId => $productItems) {
                if (! $productId || ! $trackedProducts->has((int) $productId)) continue;

                $product = $trackedProducts->get((int) $productId);
                // If tracking was intentionally disabled after the sale, do not invent an on-hand balance.
                if ($product->stock_quantity === null) continue;

                $quantity = (int) $productItems->sum('quantity');
                $before = (int) $product->stock_quantity;
                $after = $before + $quantity;
                $product->forceFill(['stock_quantity' => $after])->save();

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'movement_type' => 'order_cancelled',
                    'quantity_delta' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => 'بازگشت موجودی پس از لغو سفارش '.$order->number,
                ]);
            }

            $from = $order->status;
            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancel_reason' => $reason,
                'cancelled_at' => now(),
                'status_changed_at' => now(),
            ])->save();
            OrderStatusHistory::query()->create([
                'order_id' => $order->id, 'user_id' => $userId,
                'from_status' => $from->value, 'to_status' => OrderStatus::Cancelled->value, 'reason' => $reason,
            ]);
            $this->audit->record('order.cancelled', $order, ['status' => $from->value], ['status' => OrderStatus::Cancelled->value], $reason, $userId);

            return $order;
        }, attempts: 3);
    }
}
