<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $reservations = Reservation::query()
            ->with('table:id,name')
            ->where('customer_id', $request->user()->id)
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('customer.account', [
            'customer' => $request->user(),
            'reservations' => $reservations,
        ]);
    }
}
