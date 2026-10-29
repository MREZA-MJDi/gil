<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'phone' => PhoneNumber::normalize((string) $request->input('phone')),
            'idempotency_key' => trim((string) $request->header('Idempotency-Key', $request->input('idempotency_key'))),
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'party_size' => ['required', 'integer', 'min:1', 'max:30'],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:360'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ], [
            'starts_at.after' => 'زمان رزرو باید در آینده باشد.',
            'phone.regex' => 'شماره موبایل را معتبر وارد کنید؛ نمونه: 09121234567',
        ]);

        if (! PhoneNumber::isValid($data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'شماره موبایل معتبر نیست.']);
        }

        $startsAt = Carbon::parse($data['starts_at']);
        $duration = (int) $data['duration_minutes'];
        $endsAt = $startsAt->copy()->addMinutes($duration);
        $hash = hash('sha256', json_encode([
            'customer_name' => trim($data['customer_name']),
            'phone' => $data['phone'],
            'party_size' => (int) $data['party_size'],
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => $duration,
            'notes' => isset($data['notes']) ? trim($data['notes']) : null,
        ], JSON_THROW_ON_ERROR));

        $reservation = DB::transaction(function () use ($data, $startsAt, $endsAt, $duration, $hash) {
            $existing = Reservation::query()->where('idempotency_key', $data['idempotency_key'])->first();

            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این درخواست قبلاً با اطلاعات متفاوت ثبت شده است. صفحه را تازه کنید.']);
                }

                return [$existing, false];
            }

            $reservation = Reservation::query()->create([
                'reference' => 'RSV-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => trim($data['customer_name']),
                'phone' => $data['phone'],
                'email' => null,
                'party_size' => (int) $data['party_size'],
                'starts_at' => $startsAt,
                'duration_minutes' => $duration,
                'ends_at' => $endsAt,
                'status' => ReservationStatus::Pending,
                'source' => 'public',
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'idempotency_key' => $data['idempotency_key'],
                'idempotency_hash' => $hash,
                'created_by' => null,
            ]);

            return [$reservation, true];
        }, attempts: 3);

        [$reservation, $created] = $reservation;

        return response()->json([
            'message' => 'درخواست رزرو ثبت شد؛ پس از بررسی مجموعه، وضعیت آن تأیید می‌شود.',
            'data' => [
                'reference' => $reservation->reference,
                'customer_name' => $reservation->customer_name,
                'starts_at' => $reservation->starts_at?->toIso8601String(),
                'party_size' => $reservation->party_size,
                'status' => $reservation->status->value,
            ],
        ], $created ? 201 : 200);
    }
}
