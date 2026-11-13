<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $customer = $request->user();

        $reservations = Reservation::query()
            ->with('table:id,name')
            ->where('customer_id', $customer->id)
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(10);

        $orders = Order::query()
            ->with([
                'items',
                'customerFeedback' => fn ($query) => $query->where('customer_id', $customer->id),
            ])
            ->where('customer_id', $customer->id)
            ->where('status', OrderStatus::Delivered->value)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $feedback = CustomerFeedback::query()
            ->with(['order:id,number', 'responder:id,name'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'feedback_page');

        return view('customer.account', [
            'customer' => $customer,
            'reservations' => $reservations,
            'orders' => $orders,
            'feedback' => $feedback,
        ]);
    }
}
