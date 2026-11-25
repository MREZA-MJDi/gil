<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\SiteMediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_a_future_reservation_from_the_public_page(): void
    {
        $startsAt = Carbon::now(config('app.timezone'))->addDays(3)->setTime(18, 30)->format('Y-m-d\\TH:i');

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
            'source' => 'website',
            'phone' => '+989121234567',
            'customer_name' => 'مشتری آزمایشی',
        ]);
    }

    public function test_reservation_form_preserves_duration_after_validation_error(): void
    {
        $this->from(route('public.reservations.create'))
            ->post(route('public.reservations.store'), [
                'customer_name' => 'مشتری آزمایشی',
                'phone' => '09121234567',
                'purpose' => 'table',
                'party_size' => 2,
                'starts_at' => 'not-a-date',
                'duration_minutes' => 180,
                'idempotency_key' => 'public-reserve-old-input-0001',
                'website' => '',
            ])
            ->assertRedirect(route('public.reservations.create'))
            ->assertSessionHasErrors(['starts_at']);

        $this->get(route('public.reservations.create'))
            ->assertOk()
            ->assertSee('<option value="180" selected>۱۸۰ دقیقه</option>', false)
            ->assertDontSee('<option value="90" selected>۹۰ دقیقه</option>', false);
    }

    public function test_retried_public_reservation_does_not_create_a_duplicate(): void
    {
        $payload = [
            'customer_name' => 'مشتری تکراری شبکه',
            'phone' => '09121234568',
            'party_size' => 2,
            'starts_at' => Carbon::now(config('app.timezone'))->addDays(4)->setTime(19, 0)->format('Y-m-d\\TH:i'),
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

    public function test_reservation_intro_uses_event_gallery_slot_and_managed_alt_text(): void
    {
        $assets = [
            ['slot' => 'gallery_1', 'path' => 'site-media/gallery_1/space.webp', 'alt_text' => 'فضای داخلی ثبت‌شده'],
            ['slot' => 'gallery_3', 'path' => 'site-media/gallery_3/event.webp', 'alt_text' => 'مراسم ثبت‌شده'],
        ];

        foreach ($assets as $asset) {
            SiteMediaAsset::query()->create($asset);
        }

        $response = $this->get(route('public.reservations.create'))->assertOk();

        $matched = preg_match(
            '/<div class="reserve-side-photo">\\s*<img src="([^"]+)" alt="([^"]+)"/',
            $response->getContent(),
            $reservationImage,
        );

        $this->assertSame(1, $matched, 'The reservation intro image should be rendered.');
        $this->assertStringEndsWith($assets[1]['path'], $reservationImage[1]);
        $this->assertSame($assets[1]['alt_text'], $reservationImage[2]);
    }
}
