<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FeedbackStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $customer = $request->user();
        abort_unless($customer !== null && ! $customer->is_admin, 404);

        $data = $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'kind' => ['required', Rule::in(['complaint', 'suggestion', 'praise'])],
            'rating' => ['required', 'integer', 'between:1,5'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'kind.required' => 'نوع بازخورد را انتخاب کن.',
            'rating.required' => 'امتیازت را ثبت کن.',
            'rating.between' => 'امتیاز باید بین ۱ تا ۵ باشد.',
            'message.required' => 'متن بازخورد را بنویس.',
            'message.min' => 'متن بازخورد باید حداقل ۵ نویسه باشد.',
        ]);

        $phone = PhoneNumber::normalize((string) $customer->phone);
        $feedback = DB::transaction(function () use ($data, $customer, $phone, $audit) {
            $order = null;

            if (! empty($data['order_id'])) {
                $order = Order::query()
                    ->whereKey((int) $data['order_id'])
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $order
                    || $order->status !== OrderStatus::Delivered
                    || (int) $order->customer_id !== (int) $customer->id
                ) {
                    throw ValidationException::withMessages([
                        'order_id' => 'فقط برای سفارش تحویل‌شده‌ای که با شماره موبایل حساب تو ثبت شده می‌توان بازخورد فرستاد.',
                    ]);
                }

                $alreadySubmitted = CustomerFeedback::query()
                    ->where('customer_id', $customer->id)
                    ->where('order_id', $order->id)
                    ->exists();

                if ($alreadySubmitted) {
                    throw ValidationException::withMessages([
                        'order_id' => 'برای این سفارش قبلاً بازخورد ثبت کرده‌ای.',
                    ]);
                }
            }

            $feedback = CustomerFeedback::query()->create([
                'order_id' => $order?->id,
                'customer_id' => $customer->id,
                'customer_name' => trim((string) $customer->name),
                'phone' => $phone,
                'email' => $customer->email,
                'kind' => $data['kind'],
                'rating' => (int) $data['rating'],
                'message' => trim($data['message']),
                'status' => FeedbackStatus::New,
            ]);

            $audit->record('feedback.created', $feedback, [], [
                'order_id' => $feedback->order_id,
                'customer_id' => $customer->id,
                'kind' => $feedback->kind,
                'rating' => $feedback->rating,
                'status' => FeedbackStatus::New->value,
            ], null, $customer->id);

            return $feedback;
        }, attempts: 3);

        return redirect()
            ->to(route('customer.dashboard').'#customer-feedback')
            ->with('status', 'بازخوردت با موفقیت ثبت شد؛ پاسخ مجموعه را در همین حساب پیگیری کن.');
    }
}
