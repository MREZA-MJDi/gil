<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashShiftService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(int $openingCash, ?string $note = null, ?int $userId = null, ?string $cashierName = null): CashShift
    {
        if ($openingCash < 0) {
            throw ValidationException::withMessages(['opening_cash' => 'موجودی اولیه نمی‌تواند منفی باشد.']);
        }

        $name = trim((string) $cashierName);
        if ($name === '') {
            $name = User::query()->whereKey($userId)->value('name') ?: 'اپراتور صندوق';
        }

        return DB::transaction(function () use ($openingCash, $note, $userId, $name) {
            if (CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['shift' => 'یک شیفت صندوق از قبل باز است.']);
            }

            $shift = CashShift::query()->create([
                'opened_by' => $userId,
                'cashier_name' => $name,
                'status' => 'open',
                'open_lock' => true,
                'opening_cash' => $openingCash,
                'opening_note' => $note,
                'opened_at' => now(),
            ]);
            $this->audit->record('cash_shift.opened', $shift, [], [
                'opening_cash' => $openingCash,
                'cashier_name' => $name,
            ], $note, $userId);

            return $shift->load('opener:id,name');
        }, attempts: 3);
    }

    public function close(int $shiftId, int $countedCash, ?string $note = null, ?int $userId = null): CashShift
    {
        if ($countedCash < 0) {
            throw ValidationException::withMessages(['counted_cash' => 'وجه شمارش‌شده نمی‌تواند منفی باشد.']);
        }

        return DB::transaction(function () use ($shiftId, $countedCash, $note, $userId) {
            $shift = CashShift::query()->whereKey($shiftId)->lockForUpdate()->firstOrFail();
            if ($shift->status !== 'open') {
                throw ValidationException::withMessages(['shift' => 'این شیفت قبلاً بسته شده است.']);
            }

            $closedAt = now();
            $in = (int) $shift->movements()->where('direction', 'in')->sum('amount');
            $out = (int) $shift->movements()->where('direction', 'out')->sum('amount');
            $expected = (int) $shift->opening_cash + $in - $out;

            $orders = Order::query()->whereBetween('created_at', [$shift->opened_at, $closedAt]);
            $ordersCount = (clone $orders)->count();
            $cancelledOrders = (clone $orders)->where('status', 'cancelled')->count();
            $grossSales = (int) (clone $orders)->where('status', '!=', 'cancelled')->sum('total');

            $paymentsByMethod = Payment::query()
                ->where('status', 'succeeded')
                ->whereBetween('paid_at', [$shift->opened_at, $closedAt])
                ->selectRaw('method, SUM(amount) AS total')
                ->groupBy('method')
                ->pluck('total', 'method')
                ->map(fn ($amount) => (int) $amount)
                ->all();

            $refundsByMethod = Refund::query()
                ->whereBetween('refunded_at', [$shift->opened_at, $closedAt])
                ->selectRaw('method, SUM(amount) AS total')
                ->groupBy('method')
                ->pluck('total', 'method')
                ->map(fn ($amount) => (int) $amount)
                ->all();

            $paymentsTotal = array_sum($paymentsByMethod);
            $refundsTotal = array_sum($refundsByMethod);
            $cashReceived = $paymentsByMethod['cash'] ?? 0;
            $cardReceived = $paymentsByMethod['card_terminal'] ?? 0;
            $otherReceived = max(0, $paymentsTotal - $cashReceived - $cardReceived);

            $shift->forceFill([
                'status' => 'closed',
                'open_lock' => null,
                'closed_by' => $userId,
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'difference' => $countedCash - $expected,
                'closing_note' => $note,
                'closed_at' => $closedAt,
                'duration_minutes' => max(0, (int) $shift->opened_at->diffInMinutes($closedAt)),
                'orders_count' => $ordersCount,
                'cancelled_orders_count' => $cancelledOrders,
                'gross_sales' => $grossSales,
                'payments_total' => $paymentsTotal,
                'cash_payments_total' => $cashReceived,
                'card_payments_total' => $cardReceived,
                'other_payments_total' => $otherReceived,
                'refunds_total' => $refundsTotal,
                'net_collections_total' => max(0, $paymentsTotal - $refundsTotal),
            ])->save();

            $this->audit->record('cash_shift.closed', $shift, [], [
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'difference' => $countedCash - $expected,
                'orders_count' => $ordersCount,
                'gross_sales' => $grossSales,
                'payments_total' => $paymentsTotal,
                'refunds_total' => $refundsTotal,
            ], $note, $userId);

            return $shift->load(['opener:id,name', 'closer:id,name']);
        }, attempts: 3);
    }

    public function manualMovement(int $shiftId, string $direction, int $amount, string $reason, string $key, ?int $userId = null): CashMovement
    {
        if (! in_array($direction, ['in', 'out'], true) || $amount < 1) {
            throw ValidationException::withMessages(['movement' => 'نوع یا مبلغ حرکت صندوق معتبر نیست.']);
        }

        return DB::transaction(function () use ($shiftId, $direction, $amount, $reason, $key, $userId) {
            $shift = CashShift::query()->whereKey($shiftId)->lockForUpdate()->firstOrFail();
            if ($shift->status !== 'open') {
                throw ValidationException::withMessages(['shift' => 'شیفت بسته شده است.']);
            }

            $existing = CashMovement::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->cash_shift_id !== $shiftId || $existing->direction !== $direction || $existing->amount !== $amount || $existing->reason !== $reason) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید برای عملیات دیگری استفاده شده است.']);
                }

                return $existing;
            }

            $movement = CashMovement::query()->create([
                'cash_shift_id' => $shift->id,
                'user_id' => $userId,
                'direction' => $direction,
                'type' => 'manual',
                'amount' => $amount,
                'reason' => $reason,
                'idempotency_key' => $key,
                'occurred_at' => now(),
            ]);
            $this->audit->record('cash_shift.manual_movement', $movement, [], [
                'direction' => $direction,
                'amount' => $amount,
            ], $reason, $userId);

            return $movement;
        }, attempts: 3);
    }
}
