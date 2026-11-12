<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashShiftRequest;
use App\Http\Requests\ManualCashMovementRequest;
use App\Http\Requests\OpenCashShiftRequest;
use App\Models\CashShift;
use App\Services\CashShiftService;
use Illuminate\Http\JsonResponse;

class CashShiftController extends Controller
{
    public function current(): JsonResponse
    {
        $shift = CashShift::query()->where('status', 'open')->where('open_lock', true)->first();

        if ($shift) {
            $shift->setAttribute('cash_in_total', (int) $shift->movements()->where('direction', 'in')->sum('amount'));
            $shift->setAttribute('cash_out_total', (int) $shift->movements()->where('direction', 'out')->sum('amount'));
            $shift->setAttribute('expected_cash', (int) $shift->opening_cash + $shift->cash_in_total - $shift->cash_out_total);
            $shift->setRelation('movements', $shift->movements()->latest('id')->limit(6)->get());
        }

        return response()->json(['data' => $shift]);
    }

    public function open(OpenCashShiftRequest $request, CashShiftService $shifts): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['data' => $shifts->open($data['opening_cash'], $data['note'] ?? null, $request->user()->id)], 201);
    }

    public function close(CloseCashShiftRequest $request, CashShift $cashShift, CashShiftService $shifts): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['data' => $shifts->close($cashShift->id, $data['counted_cash'], $data['note'] ?? null, $request->user()->id)]);
    }

    public function movement(ManualCashMovementRequest $request, CashShift $cashShift, CashShiftService $shifts): JsonResponse
    {
        $data = $request->validated();
        return response()->json(['data' => $shifts->manualMovement($cashShift->id, $data['direction'], $data['amount'], $data['reason'], $data['idempotency_key'], $request->user()->id)], 201);
    }
}
