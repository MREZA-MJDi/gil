<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('from'), fn ($q) => $q->where('starts_at', '>=', $request->date('from')?->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('starts_at', '<=', $request->date('to')?->endOfDay()))
            ->orderBy('starts_at');
        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 25)))));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required','string','max:150'],
            'phone' => ['required','string','max:30'],
            'email' => ['nullable','email','max:150'],
            'party_size' => ['required','integer','min:1','max:50'],
            'starts_at' => ['required','date','after:now'],
            'notes' => ['nullable','string','max:1000'],
        ]);
        $reservation = DB::transaction(function () use ($data, $request, $audit) {
            $reservation = Reservation::query()->create([
                ...$data,
                'reference' => 'RSV-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'status' => ReservationStatus::Pending,
                'source' => 'staff',
                'created_by' => $request->user()->id,
            ]);
            $audit->record('reservation.created', $reservation, [], $reservation->only(['reference','party_size','starts_at','status']), null, $request->user()->id);
            return $reservation;
        });
        return response()->json(['data' => $reservation], 201);
    }

    public function transition(Request $request, Reservation $reservation, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ReservationStatus::class)]]);
        $old = $reservation->status;
        $reservation->forceFill(['status' => $data['status']])->save();
        $audit->record('reservation.status_changed', $reservation, ['status' => $old->value], ['status' => $reservation->status->value], null, $request->user()->id);
        return response()->json(['data' => $reservation]);
    }
}
