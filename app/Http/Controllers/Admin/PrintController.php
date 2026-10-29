<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PrintType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrintLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrintController extends Controller
{
    public function invoice(Order $order): JsonResponse
    {
        $order->load(['table:id,name', 'items', 'payments.refunds', 'refunds']);
        return response()->json(['type' => PrintType::Invoice->value, 'data' => $order]);
    }

    public function receipt(Order $order): JsonResponse
    {
        $order->load(['table:id,name', 'items', 'payments.refunds', 'refunds']);
        abort_if($order->payments->isEmpty(), 422, 'برای این سفارش پرداختی ثبت نشده است.');
        return response()->json(['type' => PrintType::Receipt->value, 'data' => $order]);
    }

    public function kitchen(Order $order): JsonResponse
    {
        $order->load(['table:id,name', 'items']);
        return response()->json(['type' => PrintType::Kitchen->value, 'data' => $order->only(['number','channel','status','customer_note','created_at']) + ['table' => $order->table, 'items' => $order->items]]);
    }

    public function log(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(PrintType::class)],
            'payment_id' => ['nullable','integer','exists:payments,id'],
            'is_reprint' => ['sometimes','boolean'],
        ]);
        if (isset($data['payment_id']) && ! $order->payments()->whereKey($data['payment_id'])->exists()) abort(422, 'پرداخت متعلق به این سفارش نیست.');

        $wasPrintedBefore = PrintLog::query()
            ->where('order_id', $order->id)
            ->where('type', $data['type'])
            ->exists();

        $log = PrintLog::query()->create([
            'order_id' => $order->id,
            'payment_id' => $data['payment_id'] ?? null,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'document_number' => $order->number ?: 'KG-'.now()->format('ymd').'-'.$order->id,
            'is_reprint' => (bool) ($data['is_reprint'] ?? false) || $wasPrintedBefore,
            'printed_at' => now(),
        ]);
        return response()->json(['data' => $log], 201);
    }
}
