<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderWebhookController extends Controller
{
    public function store(Request $request, string $provider, OrderService $orders): JsonResponse
    {
        abort_unless(in_array($provider, ['snappfood', 'partner'], true), 404);

        $secret = (string) config('integrations.order_webhook_secret');
        if (strlen($secret) < 32) {
            return response()->json(['message' => 'ورود سفارش بیرونی هنوز توسط مدیر سامانه پیکربندی نشده است.'], 503);
        }

        $received = trim((string) $request->header('X-Gil-Signature'));
        if (str_starts_with(strtolower($received), 'sha256=')) $received = substr($received, 7);
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (strlen($received) !== 64 || !hash_equals($expected, strtolower($received))) {
            return response()->json(['message' => 'امضای درخواست معتبر نیست.'], 401);
        }

        $payload = $request->validate([
            'external_order_id' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.options' => ['sometimes', 'array', 'max:20'],
            'items.*.options.*' => ['integer', 'exists:product_options,id'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'discount' => ['sometimes', 'integer', 'min:0', 'max:1000000000'],
        ]);

        $items = array_map(static fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
            'options' => array_map(static fn ($id) => ['id' => (int) $id], $item['options'] ?? []),
            'note' => $item['note'] ?? null,
        ], $payload['items']);

        $externalId = trim($payload['external_order_id']);
        $input = [
            'channel' => 'online',
            'source' => $provider,
            'external_provider' => $provider,
            'external_order_id' => $externalId,
            'items' => $items,
            'discount' => (int) ($payload['discount'] ?? 0),
            'customer_name' => $payload['customer_name'] ?? null,
            'customer_phone' => $payload['customer_phone'] ?? null,
            'customer_note' => $payload['customer_note'] ?? null,
            'internal_note' => 'ورود از اتصال بیرونی '.$provider.'؛ وضعیت پرداخت پلتفرم باید جداگانه تطبیق داده شود.',
            'idempotency_key' => 'ext:'.$provider.':'.substr(hash('sha256', $externalId), 0, 40),
        ];

        $order = $orders->create($input, null);

        return response()->json(['data' => [
            'number' => $order->number,
            'external_order_id' => $order->external_order_id,
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'total' => (int) $order->total,
        ]], $order->wasRecentlyCreated ? 201 : 200);
    }
}
