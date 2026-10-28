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
}
