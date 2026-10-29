<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashShiftService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(int $openingCash, ?string $note = null, ?int $userId = null): CashShift
    {
        if ($openingCash < 0) throw ValidationException::withMessages(['opening_cash' => 'موجودی اولیه نمی‌تواند منفی باشد.']);

        return DB::transaction(function () use ($openingCash, $note, $userId) {
            if (CashShift::query()->where('status', 'open')->where('open_lock', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['shift' => 'یک شیفت صندوق از قبل باز است.']);
            }
            $shift = CashShift::query()->create([
                'opened_by' => $userId, 'status' => 'open', 'open_lock' => true,
                'opening_cash' => $openingCash, 'opening_note' => $note, 'opened_at' => now(),
            ]);
            $this->audit->record('cash_shift.opened', $shift, [], ['opening_cash' => $openingCash], $note, $userId);
            return $shift;
        }, attempts: 3);
    }

    public function close(int $shiftId, int $countedCash, ?string $note = null, ?int $userId = null): CashShift
    {
        if ($countedCash < 0) throw ValidationException::withMessages(['counted_cash' => 'وجه شمارش‌شده نمی‌تواند منفی باشد.']);

        return DB::transaction(function () use ($shiftId, $countedCash, $note, $userId) {
            $shift = CashShift::query()->whereKey($shiftId)->lockForUpdate()->firstOrFail();
            if ($shift->status !== 'open') throw ValidationException::withMessages(['shift' => 'این شیفت قبلاً بسته شده است.']);

            $in = (int) $shift->movements()->where('direction', 'in')->sum('amount');
            $out = (int) $shift->movements()->where('direction', 'out')->sum('amount');
            $expected = (int) $shift->opening_cash + $in - $out;
            $shift->forceFill([
                'status' => 'closed', 'open_lock' => null, 'closed_by' => $userId,
                'expected_cash' => $expected, 'counted_cash' => $countedCash,
                'difference' => $countedCash - $expected, 'closing_note' => $note, 'closed_at' => now(),
            ])->save();
            $this->audit->record('cash_shift.closed', $shift, [], [
                'expected_cash' => $expected, 'counted_cash' => $countedCash,
                'difference' => $countedCash - $expected,
            ], $note, $userId);
            return $shift;
        }, attempts: 3);
    }

    public function manualMovement(int $shiftId, string $direction, int $amount, string $reason, string $key, ?int $userId = null): CashMovement
    {
        if (! in_array($direction, ['in', 'out'], true) || $amount < 1) {
            throw ValidationException::withMessages(['movement' => 'نوع یا مبلغ حرکت صندوق معتبر نیست.']);
        }
        return DB::transaction(function () use ($shiftId, $direction, $amount, $reason, $key, $userId) {
            $shift = CashShift::query()->whereKey($shiftId)->lockForUpdate()->firstOrFail();
            if ($shift->status !== 'open') throw ValidationException::withMessages(['shift' => 'شیفت بسته شده است.']);
            $existing = CashMovement::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->cash_shift_id !== $shiftId || $existing->direction !== $direction || $existing->amount !== $amount || $existing->reason !== $reason) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید برای عملیات دیگری استفاده شده است.']);
                }
                return $existing;
            }
            $movement = CashMovement::query()->create([
                'cash_shift_id' => $shift->id, 'user_id' => $userId, 'direction' => $direction,
                'type' => 'manual', 'amount' => $amount, 'reason' => $reason,
                'idempotency_key' => $key, 'occurred_at' => now(),
            ]);
            $this->audit->record('cash_shift.manual_movement', $movement, [], [
                'direction' => $direction, 'amount' => $amount,
            ], $reason, $userId);
            return $movement;
        }, attempts: 3);
    }
}
