<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $phone = '+989121234567'): User
    {
        return User::factory()->create([
            'name' => 'مشتری آزمایشی',
            'phone' => $phone,
            'email' => null,
            'password' => 'customer-test-password-12345',
            'is_admin' => false,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'مدیر آزمایشی',
            'phone' => '+989121234599',
            'email' => 'admin-test@example.test',
            'password' => 'admin-test-password-12345',
            'is_admin' => true,
        ]);
    }

    public function test_guest_must_sign_in_or_register_before_opening_the_reservation_form(): void
    {
        $this->get(route('public.reservations.create'))
            ->assertRedirect(route('customer.login'));

        $this->post(route('public.reservations.store'), [])
            ->assertRedirect(route('customer.login'));

        $this->get(route('customer.login'))->assertOk();
        $this->get(route('customer.register'))->assertOk();
    }

    public function test_customer_can_register_and_is_signed_in_to_their_account(): void
    {
        $this->post(route('customer.register.store'), [
            'name' => 'مریم مشتری',
            'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'email' => 'maryam@example.test',
            'password' => 'customer-register-password-123',
            'password_confirmation' => 'customer-register-password-123',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'مریم مشتری',
            'phone' => '+989121234567',
            'email' => 'maryam@example.test',
            'is_admin' => false,
        ]);

        $this->get(route('customer.dashboard'))->assertOk()->assertSee('رزروهای من');
        $this->get('/admin')->assertForbidden();
    }

    public function test_customer_can_log_in_with_normalized_mobile_number(): void
    {
        $customer = $this->customer();

        $this->post(route('customer.login.store'), [
            'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'password' => 'customer-test-password-12345',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_reservation_is_bound_to_the_signed_in_customer_and_status_is_visible_after_admin_review(): void
    {
        $customer = $this->customer();
        $startsAt = Carbon::now(config('app.timezone'))->addDays(5)->setTime(18, 30)->format('Y-m-d\TH:i');

        $this->actingAs($customer)->postJson(route('public.reservations.store'), [
            'customer_name' => 'نام دستکاری‌شده',
            'phone' => '09120000000',
            'party_size' => 3,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'purpose' => 'birthday',
            'idempotency_key' => 'customer-reservation-status-0001',
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $reservation = Reservation::query()->where('idempotency_key', 'customer-reservation-status-0001')->firstOrFail();

        $this->assertSame($customer->id, $reservation->customer_id);
        $this->assertSame($customer->name, $reservation->customer_name);
        $this->assertSame($customer->phone, $reservation->phone);

        $table = RestaurantTable::query()->create([
            'name' => 'میز مشتری تست',
            'token' => Str::random(48),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patchJson('/admin/api/reservations/'.$reservation->id.'/status', [
                'status' => 'confirmed',
                'restaurant_table_id' => $table->id,
            ])->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($reservation->reference)
            ->assertSee('تأیید شده')
            ->assertSee('میز مشتری تست');
    }

    public function test_customer_can_only_see_their_own_reservations(): void
    {
        $owner = $this->customer('+989121234567');
        $other = $this->customer('+989121234568');
        $startsAt = Carbon::now(config('app.timezone'))->addDays(6)->setTime(18, 0);

        $own = Reservation::query()->create([
            'reference' => 'WEB-OWN-0001',
            'customer_id' => $owner->id,
            'customer_name' => $owner->name,
            'phone' => $owner->phone,
            'party_size' => 2,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'pending',
            'source' => 'website',
            'idempotency_key' => 'customer-own-reservation-0001',
            'idempotency_hash' => hash('sha256', 'own'),
        ]);
        Reservation::query()->create([
            'reference' => 'WEB-OTHER-0001',
            'customer_id' => $other->id,
            'customer_name' => $other->name,
            'phone' => $other->phone,
            'party_size' => 2,
            'starts_at' => $startsAt->copy()->addDay(),
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addDay()->addMinutes(90),
            'status' => 'pending',
            'source' => 'website',
            'idempotency_key' => 'customer-other-reservation-0001',
            'idempotency_hash' => hash('sha256', 'other'),
        ]);

        $this->actingAs($owner)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($own->reference)
            ->assertDontSee('WEB-OTHER-0001');
    }

    public function test_admin_cannot_use_customer_portal_and_customer_cannot_use_admin_dashboard(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('customer.dashboard'))->assertForbidden();

        $customer = $this->customer();
        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_customer_logout_ends_only_the_current_session(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('customer.logout'))
            ->assertRedirect(route('customer.login'));

        $this->assertGuest();
        $this->get(route('customer.dashboard'))->assertRedirect(route('customer.login'));
    }

    public function test_public_navigation_uses_one_role_aware_profile_icon_without_staff_labels(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="profile-link"', false)
            ->assertSee(route('customer.login'))
            ->assertDontSee('ورود مدیر')
            ->assertDontSee('ورود مشتری');

        $customer = $this->customer();

        $this->actingAs($customer)->get(route('home'))
            ->assertOk()
            ->assertSee('class="profile-link"', false)
            ->assertSee(route('customer.dashboard'))
            ->assertDontSee('ورود مدیر');

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertSee('class="profile-link"', false)
            ->assertSee(route('admin.dashboard'))
            ->assertDontSee('ورود مدیر');
    }

    public function test_admin_can_sign_in_through_the_same_generic_profile_login_form(): void
    {
        $admin = $this->admin();
        config(['admin.phone' => $admin->phone]);

        $this->get(route('customer.login'))
            ->assertOk()
            ->assertDontSee('ورود مدیر')
            ->assertSee('ورود به حساب');

        $this->post(route('customer.login.store'), [
            'phone' => '۰۹۱۲۱۲۳۴۵۹۹',
            'password' => 'admin-test-password-12345',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

}
