<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data, ?int $userId = null): Expense
    {
        return DB::transaction(function () use ($data, $userId) {
            $method = PaymentMethod::from($data['payment_method']);
            $isPaid = (bool) ($data['is_paid'] ?? true);
            $shift = null;
            if ($isPaid && $method === PaymentMethod::Cash) {
                $shift = CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->first();
                if (! $shift) throw ValidationException::withMessages(['cash_shift' => 'برای هزینه نقدی باید شیفت صندوق باز باشد.']);
            }

            $expense = Expense::query()->create([
                'cash_shift_id' => $shift?->id, 'user_id' => $userId,
                'title' => $data['title'], 'category' => $data['category'],
                'amount' => (int) $data['amount'], 'payment_method' => $method->value,
                'is_paid' => $isPaid, 'paid_at' => $isPaid ? now() : null,
                'receipt_path' => $data['receipt_path'] ?? null, 'note' => $data['note'] ?? null,
            ]);

            if ($shift && $expense->is_paid) {
                CashMovement::query()->create([
                    'cash_shift_id' => $shift->id, 'user_id' => $userId, 'expense_id' => $expense->id,
                    'direction' => 'out', 'type' => 'expense', 'amount' => $expense->amount,
                    'reason' => $expense->title, 'idempotency_key' => 'expense:'.$expense->id, 'occurred_at' => now(),
                ]);
            }
            $this->audit->record('expense.created', $expense, [], [
                'title' => $expense->title, 'amount' => $expense->amount,
                'payment_method' => $expense->payment_method, 'is_paid' => $expense->is_paid,
            ], $expense->note, $userId);
            return $expense;
        }, attempts: 3);
    }

    public function update(int $expenseId, array $data, ?int $userId = null): Expense
    {
        return DB::transaction(function () use ($expenseId, $data, $userId) {
            $expense = Expense::query()->whereKey($expenseId)->lockForUpdate()->firstOrFail();
            $before = $expense->only(['title', 'category', 'amount', 'payment_method', 'is_paid', 'note', 'cash_shift_id']);
            $movement = DB::table('cash_movements')->where('expense_id', $expense->id)->lockForUpdate()->first();
            $shift = $movement ? CashShift::query()->whereKey($movement->cash_shift_id)->lockForUpdate()->first() : null;

            if ($movement && $shift?->status !== 'open') {
                throw ValidationException::withMessages([
                    'expense' => 'این هزینه در شیفت بسته‌شده ثبت شده است؛ برای حفظ تراز صندوق، ویرایش آن مجاز نیست.',
                ]);
            }

            $method = PaymentMethod::from($data['payment_method']);
            $isPaid = (bool) $data['is_paid'];
            $nextShift = null;

            if ($isPaid && $method === PaymentMethod::Cash) {
                if ($movement) {
                    $nextShift = $shift;
                } else {
                    $nextShift = CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->first();
                    if (! $nextShift) {
                        throw ValidationException::withMessages(['cash_shift' => 'برای ثبت هزینه نقدی، ابتدا شیفت صندوق را باز کن.']);
                    }
                }
            }

            $expense->fill([
                'title' => trim($data['title']),
                'category' => $data['category'],
                'amount' => (int) $data['amount'],
                'payment_method' => $method->value,
                'is_paid' => $isPaid,
                'paid_at' => $isPaid ? ($expense->paid_at ?? now()) : null,
                'cash_shift_id' => $nextShift?->id,
                'note' => $data['note'] ?? null,
            ])->save();

            if ($movement && $nextShift) {
                DB::table('cash_movements')->where('expense_id', $expense->id)->update([
                    'amount' => $expense->amount,
                    'reason' => $expense->title,
                    'cash_shift_id' => $nextShift->id,
                ]);
            } elseif ($movement) {
                DB::table('cash_movements')->where('expense_id', $expense->id)->delete();
            } elseif ($nextShift) {
                CashMovement::query()->create([
                    'cash_shift_id' => $nextShift->id, 'user_id' => $userId, 'expense_id' => $expense->id,
                    'direction' => 'out', 'type' => 'expense', 'amount' => $expense->amount,
                    'reason' => $expense->title, 'idempotency_key' => 'expense:'.$expense->id,
                    'occurred_at' => $expense->paid_at ?? now(),
                ]);
            }

            $this->audit->record('expense.updated', $expense, $before, [
                'title' => $expense->title, 'category' => $expense->category,
                'amount' => $expense->amount, 'payment_method' => $expense->payment_method,
                'is_paid' => $expense->is_paid, 'cash_shift_id' => $expense->cash_shift_id,
            ], $expense->note, $userId);

            return $expense->fresh()->load('user:id,name');
        }, attempts: 3);
    }

    public function deleteDemo(int $expenseId, ?int $userId = null): void
    {
        DB::transaction(function () use ($expenseId, $userId) {
            $expense = Expense::query()->whereKey($expenseId)->lockForUpdate()->firstOrFail();
            if (! str_starts_with($expense->title, 'DEMO ·')) {
                throw ValidationException::withMessages(['expense' => 'فقط هزینه‌های مشخص‌شده به‌عنوان داده نمونه قابل حذف هستند.']);
            }

            $movement = DB::table('cash_movements')->where('expense_id', $expense->id)->lockForUpdate()->first();
            if ($movement) {
                $shift = CashShift::query()->whereKey($movement->cash_shift_id)->lockForUpdate()->first();
                if ($shift && $shift->status !== 'open') {
                    throw ValidationException::withMessages([
                        'expense' => 'این هزینه در شیفت بسته‌شده ثبت شده و برای حفظ سابقه مالی قابل حذف نیست.',
                    ]);
                }
                DB::table('cash_movements')->where('expense_id', $expense->id)->delete();
            }

            $this->audit->record('expense.demo_deleted', $expense, $expense->only([
                'title', 'category', 'amount', 'payment_method', 'is_paid', 'cash_shift_id',
            ]), [], 'حذف هزینه نمونه', $userId);
            $expense->delete();
        }, attempts: 3);
    }
}
