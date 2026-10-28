<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable','date_format:Y-m-d'],
            'to' => ['nullable','date_format:Y-m-d','after_or_equal:from'],
        ]);
        $from = isset($data['from']) ? now()->parse($data['from'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $to = isset($data['to']) ? now()->parse($data['to'])->endOfDay() : now()->endOfDay();
        if ($from->diffInDays($to) > 366) throw ValidationException::withMessages(['from' => 'بازه گزارش حداکثر یک سال است.']);

        $orders = Order::query()->whereBetween('created_at', [$from, $to]);
        $summary = (clone $orders)->selectRaw('COUNT(*) as orders_count')
            ->selectRaw("SUM(CASE WHEN status != ? THEN total ELSE 0 END) as registered_sales", [OrderStatus::Cancelled->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_orders", [OrderStatus::Cancelled->value])
            ->first();

        $daily = (clone $orders)->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw("SUM(CASE WHEN status != ? THEN total ELSE 0 END) as registered_sales", [OrderStatus::Cancelled->value])
            ->groupBy('day')->orderBy('day')->get();

        $payments = DB::table('payments')->where('status', 'succeeded')->whereBetween('paid_at', [$from, $to])
            ->select('method')->selectRaw('SUM(amount) as amount')->selectRaw('COUNT(*) as count')->groupBy('method')->get();
        $refunds = DB::table('refunds')->whereBetween('refunded_at', [$from, $to])->sum('amount');

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->select('order_items.product_id', 'order_items.name')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.line_total) as gross_sales')
            ->groupBy('order_items.product_id', 'order_items.name')
            ->orderByDesc('units_sold')->limit(10)->get();

        return response()->json(['data' => [
            'from' => $from->toIso8601String(), 'to' => $to->toIso8601String(),
            'summary' => [
                'orders_count' => (int) $summary->orders_count,
                'registered_sales' => (int) $summary->registered_sales,
                'cancelled_orders' => (int) $summary->cancelled_orders,
                'refunds' => (int) $refunds,
                'collections_gross' => (int) $payments->sum('amount'),
                'collections_net' => max(0, (int) $payments->sum('amount') - (int) $refunds),
            ],
            'daily' => $daily,
            'payments_by_method' => $payments,
            'top_products' => $topProducts,
            'note' => 'Registered sales, collections, refunds and profit are separate measures. Profit is not calculated until cost-of-goods/inventory data is complete.',
        ]]);
    }
}
