<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Services\AuditLogger;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicReservationController extends Controller
{
    public function create(): View
    {
        return view('public.reserve', ['settings' => RestaurantSetting::current()]);
    }

    public function received(): View
    {
        return view('public.reservation-received', ['settings' => RestaurantSetting::current()]);
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse|RedirectResponse
    {
        if (trim((string) $request->input('website')) !== '') {
            return $request->expectsJson()
                ? response()->json(['data' => ['status' => 'received']], 202)
                : redirect()->route('public.reservations.received');
        }

        $request->merge([
            'phone' => PhoneNumber::normalize((string) $request->input('phone')),
            'idempotency_key' => trim((string) $request->header('Idempotency-Key', $request->input('idempotency_key'))),
            'purpose' => $request->input('purpose', 'table'),
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! PhoneNumber::isValid(is_string($value) ? $value : null)) {
                    $fail('شماره موبایل معتبر وارد کن.');
                }
            }],
            'email' => ['nullable', 'email', 'max:150'],
            'purpose' => ['required', Rule::in(['table', 'birthday', 'consultation', 'private_event', 'catering'])],
            'occasion_name' => ['nullable', 'string', 'max:150'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:360'],
            'estimated_budget' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $startsAt = Carbon::parse($data['starts_at'], config('app.timezone'));
        $duration = (int) $data['duration_minutes'];
        $endsAt = $startsAt->copy()->addMinutes($duration);
        $canonical = [
            'customer_name' => trim($data['customer_name']),
            'phone' => $data['phone'],
            'email' => isset($data['email']) ? trim($data['email']) : null,
            'purpose' => $data['purpose'],
            'occasion_name' => isset($data['occasion_name']) ? trim($data['occasion_name']) : null,
            'party_size' => (int) $data['party_size'],
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => $duration,
            'estimated_budget' => isset($data['estimated_budget']) ? (int) $data['estimated_budget'] : null,
            'notes' => isset($data['notes']) ? trim($data['notes']) : null,
        ];
        $hash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        $result = DB::transaction(function () use ($data, $startsAt, $endsAt, $duration, $hash, $canonical, $audit) {
            $existing = Reservation::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'این درخواست قبلاً با اطلاعات دیگری ثبت شده است؛ صفحه را تازه کن.',
                    ]);
                }

                return ['reservation' => $existing, 'created' => false];
            }

            $reservation = Reservation::query()->create([
                'reference' => 'WEB-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => $canonical['customer_name'],
                'phone' => $canonical['phone'],
                'email' => $canonical['email'],
                'party_size' => $canonical['party_size'],
                'starts_at' => $startsAt,
                'duration_minutes' => $duration,
                'ends_at' => $endsAt,
                'status' => ReservationStatus::Pending,
                'source' => 'website',
                'purpose' => $canonical['purpose'],
                'occasion_name' => $canonical['occasion_name'],
                'estimated_budget' => $canonical['estimated_budget'],
                'notes' => $canonical['notes'],
                'idempotency_key' => $data['idempotency_key'],
                'idempotency_hash' => $hash,
                'created_by' => null,
            ]);

            $audit->record('reservation.website_request_created', $reservation, [], [
                'reference' => $reservation->reference,
                'purpose' => $reservation->purpose,
                'party_size' => $reservation->party_size,
                'starts_at' => $reservation->starts_at,
            ], null, null);

            return ['reservation' => $reservation, 'created' => true];
        }, attempts: 3);

        /** @var Reservation $reservation */
        $reservation = $result['reservation'];

        if ($request->expectsJson()) {
            return response()->json(['data' => [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'status' => $reservation->status->value,
                'party_size' => (int) $reservation->party_size,
                'starts_at' => $reservation->starts_at?->toIso8601String(),
                'source' => $reservation->source,
            ]], $result['created'] ? 201 : 200);
        }

        return redirect()->route('public.reservations.received');
    }
}
