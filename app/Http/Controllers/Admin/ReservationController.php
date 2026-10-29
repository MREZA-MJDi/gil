<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()->with('table:id,name')->with('creator:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', Carbon::parse($request->date('from'))->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<=', Carbon::parse($request->date('to'))->endOfDay()))
            ->orderBy('starts_at');
        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 25)))));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $request->merge([
            'idempotency_key' => trim((string) $request->header('Idempotency-Key', $request->input('idempotency_key'))),
        ]);
        $data = $request->validate([
            'restaurant_table_id' => ['nullable','integer','exists:restaurant_tables,id'],
            'customer_name' => ['required','string','max:150'],
            'phone' => ['required','string','max:30'],
            'email' => ['nullable','email','max:150'],
            'party_size' => ['required','integer','min:1','max:50'],
            'starts_at' => ['required','date','after:now'],
            'duration_minutes' => ['sometimes','integer','min:30','max:360'],
            'notes' => ['nullable','string','max:1000'],
            'idempotency_key' => ['required','string','min:16','max:64','regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $startsAt = Carbon::parse($data['starts_at']);
        $duration = (int) ($data['duration_minutes'] ?? 90);
        $endsAt = $startsAt->copy()->addMinutes($duration);
        $idempotencyHash = hash('sha256', json_encode([
            'restaurant_table_id' => isset($data['restaurant_table_id']) ? (int) $data['restaurant_table_id'] : null,
            'customer_name' => trim($data['customer_name']),
            'phone' => trim($data['phone']),
            'email' => isset($data['email']) ? trim($data['email']) : null,
            'party_size' => (int) $data['party_size'],
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => $duration,
            'notes' => isset($data['notes']) ? trim($data['notes']) : null,
        ], JSON_THROW_ON_ERROR));

        $reservation = DB::transaction(function () use ($data, $startsAt, $endsAt, $duration, $idempotencyHash, $request, $audit) {
            $existing = Reservation::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_hash, $idempotencyHash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید قبلاً برای رزرو دیگری استفاده شده است.']);
                }
                return $existing->load('table:id,name');
            }

            $table = null;
            if (! empty($data['restaurant_table_id'])) {
                $table = RestaurantTable::query()->whereKey($data['restaurant_table_id'])->lockForUpdate()->first();
                if (! $table || ! $table->is_active) {
                    throw ValidationException::withMessages(['restaurant_table_id' => 'میز انتخاب‌شده فعال نیست.']);
                }

                $conflict = Reservation::query()
                    ->where('restaurant_table_id', $table->id)
                    ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value, ReservationStatus::Arrived->value])
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->exists();

                if ($conflict) throw ValidationException::withMessages(['restaurant_table_id' => 'این میز در بازه زمانی انتخاب‌شده رزرو است.']);
            }

            $reservation = Reservation::query()->create([
                ...$data,
                'reference' => 'RSV-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'starts_at' => $startsAt,
                'duration_minutes' => $duration,
                'ends_at' => $endsAt,
                'status' => ReservationStatus::Pending,
                'source' => 'staff',
                'created_by' => $request->user()->id,
                'idempotency_hash' => $idempotencyHash,
            ]);
            $audit->record('reservation.created', $reservation, [], [
                'reference' => $reservation->reference,
                'restaurant_table_id' => $reservation->restaurant_table_id,
                'party_size' => $reservation->party_size,
                'starts_at' => $reservation->starts_at,
                'ends_at' => $reservation->ends_at,
                'status' => $reservation->status->value,
            ], null, $request->user()->id);
            return $reservation->load('table:id,name');
        }, attempts: 3);

        return response()->json(['data' => $reservation], $reservation->wasRecentlyCreated ? 201 : 200);
    }

    public function transition(Request $request, Reservation $reservation, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ReservationStatus::class)]]);
        return DB::transaction(function () use ($data, $reservation, $audit, $request) {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;
            $to = ReservationStatus::from($data['status']);

            $allowed = match ($from) {
                ReservationStatus::Pending => [ReservationStatus::Confirmed, ReservationStatus::Cancelled],
                ReservationStatus::Confirmed => [ReservationStatus::Arrived, ReservationStatus::Cancelled, ReservationStatus::NoShow],
                ReservationStatus::Arrived => [ReservationStatus::Cancelled],
                ReservationStatus::Cancelled, ReservationStatus::NoShow => [],
            };
            if (! in_array($to, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'تغییر وضعیت رزرو از این مرحله مجاز نیست.']);
            }

            $locked->forceFill(['status' => $to])->save();
            $audit->record('reservation.status_changed', $locked, ['status' => $from->value], ['status' => $to->value], null, $request->user()->id);
            return response()->json(['data' => $locked->load('table:id,name')]);
        }, attempts: 3);
    }
}
