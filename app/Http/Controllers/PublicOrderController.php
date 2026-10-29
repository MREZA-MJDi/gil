<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicOrderController extends Controller
{
    public function store(Request $request, OrderService $orders): JsonResponse
    {
        $request->merge([
            'idempotency_key' => trim((string) $request->header('Idempotency-Key', $request->input('idempotency_key'))),
        ]);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.options' => ['sometimes', 'array', 'max:20'],
            'items.*.options.*.id' => ['required', 'integer', 'exists:product_options,id'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $data['channel'] = 'takeaway';
        $data['source'] = 'website';
        $data['discount'] = 0;
        $data['internal_note'] = 'ثبت مستقیم از منوی عمومی وب؛ تسویه هنگام تحویل یا در صندوق انجام شود.';

        $order = $orders->create($data, null);

        return response()->json(['data' => [
            'number' => $order->number,
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'total' => (int) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
        ]], $order->wasRecentlyCreated ? 201 : 200);
    }
}
