<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
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

        // Keep dashboard totals in SQL: loading every order into PHP makes this
        // endpoint grow linearly with the number of orders created today.
        $todayOrders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status != ? THEN total ELSE 0 END), 0) as registered_sales',
                [OrderStatus::Cancelled->value],
            )
            ->first();

        $collected = (int) DB::table('payments')
            ->where('status', TransactionStatus::Succeeded->value)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $refunded = (int) DB::table('refunds')
            ->whereBetween('refunded_at', [$start, $end])
            ->sum('amount');

        $paidTotals = DB::table('payments')
            ->select('order_id')
            ->selectRaw('SUM(amount) as paid_amount')
            ->where('status', TransactionStatus::Succeeded->value)
            ->groupBy('order_id');

        $refundTotals = DB::table('refunds')
            ->select('order_id')
            ->selectRaw('SUM(amount) as refunded_amount')
            ->groupBy('order_id');

        // Aggregate outstanding balances in the database instead of eager-loading
        // every unsettled order and its payment/refund collections into memory.
        $openInvoices = (int) Order::query()
            ->unsettled()
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->leftJoinSub($paidTotals, 'payment_totals', 'payment_totals.order_id', '=', 'orders.id')
            ->leftJoinSub($refundTotals, 'refund_totals', 'refund_totals.order_id', '=', 'orders.id')
            ->selectRaw(
                'COALESCE(SUM(CASE
                    WHEN orders.total - COALESCE(payment_totals.paid_amount, 0)
                        + COALESCE(refund_totals.refunded_amount, 0) > 0
                    THEN orders.total - COALESCE(payment_totals.paid_amount, 0)
                        + COALESCE(refund_totals.refunded_amount, 0)
                    ELSE 0
                END), 0) as open_invoice_amount',
            )
            ->value('open_invoice_amount');

        $byStatus = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $recent = Order::query()
            ->with('table:id,name')
            ->withCount('items')
            ->latest('id')
            ->limit(8)
            ->get([
                'id', 'number', 'restaurant_table_id', 'channel', 'source',
                'external_provider', 'external_order_id', 'status',
                'payment_status', 'total', 'created_at',
            ]);

        return response()->json(['data' => [
            'period' => ['from' => $start->toIso8601String(), 'to' => $end->toIso8601String()],
            'registered_sales' => (int) $todayOrders->registered_sales,
            'collections_net' => max(0, $collected - $refunded),
            'collections_gross' => $collected,
            'refunds' => $refunded,
            'orders_count' => (int) $todayOrders->orders_count,
            'orders_by_status' => $byStatus,
            'open_invoice_amount' => $openInvoices,
            'low_stock_count' => Product::query()->lowStock()->count(),
            'today_reservations_count' => Reservation::query()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereBetween('starts_at', [$start, $end])
                ->count(),
            'active_cash_shift' => CashShift::query()
                ->where('status', 'open')
                ->where('open_lock', true)
                ->first(['id', 'opened_at', 'opening_cash']),
            'recent_orders' => $recent,
        ]]);
    }
}
