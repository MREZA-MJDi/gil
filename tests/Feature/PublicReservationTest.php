<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_a_future_reservation_from_the_public_page(): void
    {
        $startsAt = Carbon::now(config('app.timezone'))->addDays(3)->setTime(18, 30)->format('Y-m-d\TH:i');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Idempotency-Key' => 'public-reserve-test-0001',
        ])->postJson(route('public.reservations.store'), [
            'customer_name' => 'مشتری آزمایشی',
            'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'party_size' => 3,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'notes' => 'درخواست پنجره',
            'idempotency_key' => 'public-reserve-test-0001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.party_size', 3);

        $this->assertDatabaseHas('reservations', [
            'source' => 'public',
            'phone' => '+989121234567',
            'customer_name' => 'مشتری آزمایشی',
        ]);
    }

    public function test_retried_public_reservation_does_not_create_a_duplicate(): void
    {
        $payload = [
            'customer_name' => 'مشتری تکراری شبکه',
            'phone' => '09121234568',
            'party_size' => 2,
            'starts_at' => Carbon::now(config('app.timezone'))->addDays(4)->setTime(19, 0)->format('Y-m-d\TH:i'),
            'duration_minutes' => 90,
            'notes' => null,
            'idempotency_key' => 'public-reserve-test-0002',
        ];

        $this->withHeaders(['Accept' => 'application/json', 'Idempotency-Key' => $payload['idempotency_key']])
            ->postJson(route('public.reservations.store'), $payload)->assertCreated();

        $this->withHeaders(['Accept' => 'application/json', 'Idempotency-Key' => $payload['idempotency_key']])
            ->postJson(route('public.reservations.store'), $payload)->assertOk();

        $this->assertSame(1, Reservation::query()->where('idempotency_key', $payload['idempotency_key'])->count());
    }
}
