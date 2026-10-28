<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(int $orderId, int $amount, PaymentMethod $method, string $key, ?string $reference = null, ?string $note = null, ?int $userId = null): Payment
    {
        $hash = hash('sha256', json_encode([
            'order_id' => $orderId, 'amount' => $amount, 'method' => $method->value,
            'reference' => $reference, 'note' => $note,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($orderId, $amount, $method, $key, $reference, $note, $userId, $hash) {
            $existing = Payment::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if (! hash_equals($existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید با داده‌های متفاوت استفاده شده است.']);
                }
                return $existing;
            }

            if ($amount < 1) throw ValidationException::withMessages(['amount' => 'مبلغ پرداخت باید مثبت باشد.']);
            if ($method === PaymentMethod::Online) {
                throw ValidationException::withMessages(['method' => 'پرداخت آنلاین تا زمان اتصال درگاه تأییدشده غیرفعال است.']);
            }

            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->status->value === 'cancelled') {
                throw ValidationException::withMessages(['order' => 'سفارش لغوشده قابل پرداخت نیست.']);
            }
            if ($amount > $order->remaining()) {
                throw ValidationException::withMessages(['amount' => 'مبلغ پرداخت از مانده صورتحساب بیشتر است.']);
            }

            $shift = null;
            if ($method === PaymentMethod::Cash) {
                $shift = CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->first();
                if (! $shift) throw ValidationException::withMessages(['cash_shift' => 'برای دریافت نقدی ابتدا شیفت صندوق را باز کنید.']);
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id, 'user_id' => $userId, 'amount' => $amount,
                'method' => $method->value, 'status' => TransactionStatus::Succeeded,
                'reference' => $reference, 'note' => $note,
                'idempotency_key' => $key, 'idempotency_hash' => $hash, 'paid_at' => now(),
            ]);

            if ($shift) {
                CashMovement::query()->create([
                    'cash_shift_id' => $shift->id, 'user_id' => $userId, 'order_id' => $order->id,
                    'payment_id' => $payment->id, 'direction' => 'in', 'type' => 'payment',
                    'amount' => $amount, 'reason' => 'Payment '.$order->number,
                    'idempotency_key' => 'payment:'.$payment->id, 'occurred_at' => now(),
                ]);
            }

            $netPaid = $order->netPaid() + $amount;
            $order->forceFill(['payment_status' => $netPaid >= $order->total ? PaymentStatus::Paid : PaymentStatus::Partial])->save();
            $this->audit->record('payment.recorded', $payment, [], [
                'order_id' => $order->id, 'amount' => $amount, 'method' => $method->value,
            ], null, $userId);

            return $payment;
        }, attempts: 3);
    }

    public function refund(int $paymentId, int $amount, string $reason, string $key, ?int $userId = null): Refund
    {
        $hash = hash('sha256', json_encode([
            'payment_id' => $paymentId, 'amount' => $amount, 'reason' => trim($reason),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($paymentId, $amount, $reason, $key, $userId, $hash) {
            $existing = Refund::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if (! hash_equals($existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید بازپرداخت با داده‌های متفاوت استفاده شده است.']);
                }
                return $existing;
            }

            $payment = Payment::query()->whereKey($paymentId)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            if ($payment->status !== TransactionStatus::Succeeded) {
                throw ValidationException::withMessages(['payment' => 'پرداخت موفق نیست.']);
            }
            if ($amount < 1 || $amount > $payment->refundable()) {
                throw ValidationException::withMessages(['amount' => 'مبلغ بازپرداخت از مبلغ قابل برگشت بیشتر است.']);
            }

            $shift = null;
            if ($payment->method === PaymentMethod::Cash) {
                $shift = CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->first();
                if (! $shift) throw ValidationException::withMessages(['cash_shift' => 'برای بازپرداخت نقدی باید شیفت صندوق باز باشد.']);
            }

            $refund = Refund::query()->create([
                'payment_id' => $payment->id, 'order_id' => $order->id, 'user_id' => $userId,
                'amount' => $amount, 'method' => $payment->method->value, 'reason' => $reason,
                'idempotency_key' => $key, 'idempotency_hash' => $hash, 'refunded_at' => now(),
            ]);
            if ($shift) {
                CashMovement::query()->create([
                    'cash_shift_id' => $shift->id, 'user_id' => $userId, 'order_id' => $order->id,
                    'payment_id' => $payment->id, 'refund_id' => $refund->id, 'direction' => 'out',
                    'type' => 'refund', 'amount' => $amount, 'reason' => $reason,
                    'idempotency_key' => 'refund:'.$refund->id, 'occurred_at' => now(),
                ]);
            }

            $netPaid = $order->netPaid() - $amount;
            $status = $netPaid <= 0 ? PaymentStatus::Refunded
                : ($netPaid < $order->total ? PaymentStatus::PartiallyRefunded : PaymentStatus::Paid);
            $order->forceFill(['payment_status' => $status])->save();
            $this->audit->record('payment.refunded', $refund, [], [
                'payment_id' => $payment->id, 'order_id' => $order->id, 'amount' => $amount,
            ], $reason, $userId);

            return $refund;
        }, attempts: 3);
    }
}
