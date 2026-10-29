<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_with_iranian_mobile_in_local_format(): void
    {
        User::factory()->create([
            'name' => 'اپراتور',
            'phone' => '+989121234567',
            'email' => null,
            'password' => 'correct-test-password-123',
            'is_admin' => true,
        ]);

        $this->post(route('admin.login.store'), [
            'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'password' => 'correct-test-password-123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_non_admin_account_cannot_enter_the_dashboard(): void
    {
        User::factory()->create([
            'phone' => '+989121234568',
            'email' => null,
            'password' => 'correct-test-password-123',
            'is_admin' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'phone' => '+989121234568',
            'password' => 'correct-test-password-123',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_registration_requires_a_configured_invitation_key(): void
    {
        config(['admin.registration_key' => null]);

        $this->post(route('admin.register.store'), [
            'name' => 'مدیر تازه',
            'phone' => '09121234567',
            'password' => 'new-test-password-123',
            'password_confirmation' => 'new-test-password-123',
            'registration_key' => 'not-configured',
        ])->assertSessionHasErrors('registration_key');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invited_admin_can_register_with_mobile_number(): void
    {
        config(['admin.registration_key' => 'test-invitation-key-that-is-long-enough']);

        $this->post(route('admin.register.store'), [
            'name' => 'مدیر تازه',
            'phone' => '09121234569',
            'password' => 'new-test-password-123',
            'password_confirmation' => 'new-test-password-123',
            'registration_key' => 'test-invitation-key-that-is-long-enough',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'phone' => '+989121234569',
            'email' => null,
            'is_admin' => true,
        ]);
        $this->assertAuthenticated();
    }

    public function test_auth_pages_are_mobile_first_and_do_not_request_email(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertDontSee('name="email"', false);

        $this->get(route('admin.register'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertDontSee('name="email"', false);
    }
}
