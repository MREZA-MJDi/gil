<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\Reservation;
use App\Support\PhoneNumber;
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

        $normalizedPhone = PhoneNumber::normalize((string) $customer->phone);
        $phoneVariants = $this->phoneVariants($customer->phone, $normalizedPhone);

        $orders = collect();
        if ($normalizedPhone !== null && $phoneVariants !== []) {
            $orders = Order::query()
                ->with([
                    'items',
                    'customerFeedback' => fn ($query) => $query->where('customer_id', $customer->id),
                ])
                ->where('status', OrderStatus::Delivered->value)
                ->whereIn('customer_phone', $phoneVariants)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->filter(fn (Order $order) => PhoneNumber::normalize((string) $order->customer_phone) === $normalizedPhone)
                ->take(20)
                ->values();
        }

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

    /**
     * Orders created before customer accounts were connected may contain
     * either the local or international form of the same phone number.
     *
     * @return list<string>
     */
    private function phoneVariants(?string $rawPhone, ?string $normalizedPhone): array
    {
        if ($normalizedPhone === null) {
            return [];
        }

        $digits = ltrim($normalizedPhone, '+');
        $variants = [$normalizedPhone, $digits, (string) $rawPhone];

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $national = substr($digits, 2);
            $variants[] = '0'.$national;
            $variants[] = $national;
        }

        return array_values(array_unique(array_filter(
            $variants,
            fn ($value) => is_string($value) && trim($value) !== ''
        )));
    }
}
