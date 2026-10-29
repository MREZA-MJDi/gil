<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $orders = Order::query()->whereBetween('created_at', [$start, $end])
            ->select(['id', 'total', 'status', 'payment_status', 'created_at'])->get();

        $collected = (int) DB::table('payments')->where('status', 'succeeded')->whereBetween('paid_at', [$start, $end])->sum('amount');
        $refunded = (int) DB::table('refunds')->whereBetween('refunded_at', [$start, $end])->sum('amount');

        $openInvoices = Order::query()->unsettled()->where('status', '!=', OrderStatus::Cancelled->value)
            ->withSum(['payments as paid_amount' => fn ($q) => $q->where('status', 'succeeded')], 'amount')
            ->withSum('refunds', 'amount')
            ->get(['id', 'total'])
            ->sum(fn (Order $order) => max(0, (int) $order->total - (int) $order->paid_amount + (int) $order->refunds_sum_amount));

        $byStatus = Order::query()->whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status');

        $recent = Order::query()->with('table:id,name')->withCount('items')->latest('id')->limit(8)
            ->get(['id', 'number', 'restaurant_table_id', 'channel', 'status', 'payment_status', 'total', 'created_at']);

        return response()->json(['data' => [
            'period' => ['from' => $start->toIso8601String(), 'to' => $end->toIso8601String()],
            'registered_sales' => (int) $orders->where('status', '!=', OrderStatus::Cancelled->value)->sum('total'),
            'collections_net' => max(0, $collected - $refunded),
            'collections_gross' => $collected,
            'refunds' => $refunded,
            'orders_count' => $orders->count(),
            'orders_by_status' => $byStatus,
            'open_invoice_amount' => (int) $openInvoices,
            'low_stock_count' => Product::query()->lowStock()->count(),
            'today_reservations_count' => Reservation::query()->whereIn('status', ['pending', 'confirmed'])->whereBetween('starts_at', [$start, $end])->count(),
            'active_cash_shift' => CashShift::query()->where('status', 'open')->where('open_lock', true)->first(['id', 'opened_at', 'opening_cash']),
            'recent_orders' => $recent,
        ]]);
    }
}
