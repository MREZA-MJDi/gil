<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhoneAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PHONE = '+989120832179';
    private const ADMIN_EMAIL = 'admin@gil.local';
    private const ADMIN_PASSWORD = 'correct-shared-admin-password-123';

    private function configureAdmin(): void
    {
        config([
            'admin.email' => self::ADMIN_EMAIL,
            'admin.phone' => self::ADMIN_PHONE,
            'admin.password' => self::ADMIN_PASSWORD,
            'admin.name' => 'مدیر خانه گیلاسی',
        ]);
    }

    private function createConfiguredAdmin(): User
    {
        $this->configureAdmin();

        return User::factory()->create([
            'name' => 'مدیر خانه گیلاسی',
            'email' => self::ADMIN_EMAIL,
            'phone' => self::ADMIN_PHONE,
            'password' => self::ADMIN_PASSWORD,
            'is_admin' => true,
        ]);
    }

    public function test_shared_admin_can_sign_in_with_configured_mobile_username_and_password(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('admin.register');

        $this->post(route('admin.login.store'), [
            'phone' => '09120832179',
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_persian_mobile_digits_and_international_format_are_normalized(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->post(route('admin.login.store'), [
            'phone' => '۰۹۱۲۰۸۳۲۱۷۹',
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        auth()->logout();

        $this->post(route('admin.login.store'), [
            'phone' => '+989120832179',
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_only_configured_mobile_and_correct_password_can_sign_in(): void
    {
        $this->createConfiguredAdmin();

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'phone' => '09120000000',
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.login'))
          ->assertSessionHasErrors('phone');

        $this->assertGuest();

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'phone' => '09120832179',
            'password' => 'wrong-password-value',
        ])->assertRedirect(route('admin.login'))
          ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_non_admin_account_cannot_use_the_shared_admin_login(): void
    {
        $this->configureAdmin();

        User::factory()->create([
            'email' => self::ADMIN_EMAIL,
            'phone' => self::ADMIN_PHONE,
            'password' => self::ADMIN_PASSWORD,
            'is_admin' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'phone' => self::ADMIN_PHONE,
            'password' => self::ADMIN_PASSWORD,
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_admin_seeder_creates_and_reuses_one_configured_shared_account(): void
    {
        config([
            'admin.email' => self::ADMIN_EMAIL,
            'admin.phone' => '09120832179',
            'admin.password' => self::ADMIN_PASSWORD,
            'admin.name' => 'مدیر خانه گیلاسی',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);

        $admin = User::query()->where('phone', self::ADMIN_PHONE)->firstOrFail();

        $this->assertSame(self::ADMIN_EMAIL, $admin->email);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check(self::ADMIN_PASSWORD, $admin->password));
    }

    public function test_logout_clears_current_device_session_and_protected_route_redirects_to_login(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->actingAs($admin)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_registration_is_disabled_for_shared_account_flow(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', [])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
