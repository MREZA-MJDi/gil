<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Requests\RefundPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->payments()->with('refunds')->get()]);
    }

    public function store(RecordPaymentRequest $request, Order $order, PaymentService $payments): JsonResponse
    {
        $data = $request->validated();
        $payment = $payments->record($order->id, $data['amount'], PaymentMethod::from($data['method']), $data['idempotency_key'], $data['reference'] ?? null, $data['note'] ?? null, $request->user()->id);
        return response()->json(['data' => $payment], 201);
    }

    public function refund(RefundPaymentRequest $request, Payment $payment, PaymentService $payments): JsonResponse
    {
        $data = $request->validated();
        $refund = $payments->refund($payment->id, $data['amount'], $data['reason'], $data['idempotency_key'], $request->user()->id);
        return response()->json(['data' => $refund], 201);
    }
}
