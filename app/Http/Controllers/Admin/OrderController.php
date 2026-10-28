<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\TransitionOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['table:id,name', 'items:id,order_id,name,quantity,line_total'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if (! in_array($status, array_column(OrderStatus::cases(), 'value'), true)) {
                    throw ValidationException::withMessages(['status' => 'فیلتر وضعیت معتبر نیست.']);
                }
                $q->where('status', $status);
            })
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')->toString()))
            ->when($request->boolean('unsettled'), fn ($q) => $q->unsettled())
            ->when($request->filled('q'), function ($q) use ($request) {
                $needle = trim($request->string('q')->toString());
                $q->where(fn ($inner) => $inner->where('number', 'like', '%'.$needle.'%')
                    ->orWhere('customer_phone', 'like', '%'.$needle.'%')
                    ->orWhereHas('table', fn ($table) => $table->where('name', 'like', '%'.$needle.'%')));
            });

        if ($request->string('sort')->toString() === 'total') {
            $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
            $query->orderBy('total', $direction)->orderByDesc('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 25)))));
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->load(['table', 'items', 'payments.refunds', 'refunds', 'statusHistory', 'printLogs'])]);
    }

    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->create($request->validated(), $request->user()->id);
        return response()->json(['data' => $order], 201);
    }

    public function transition(TransitionOrderRequest $request, Order $order, OrderService $orders): JsonResponse
    {
        $data = $request->validated();
        $updated = $orders->transition($order->id, OrderStatus::from($data['status']), $data['reason'] ?? null, $request->user()->id);
        return response()->json(['data' => $updated]);
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderService $orders): JsonResponse
    {
        $updated = $orders->cancel($order->id, $request->validated('reason'), $request->user()->id);
        return response()->json(['data' => $updated]);
    }
}
