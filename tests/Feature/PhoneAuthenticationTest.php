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

    private const ADMIN_EMAIL = 'admin@gil.local';
    private const ADMIN_PHONE = '+989120832179';
    private const ADMIN_PASSWORD = 'correct-shared-admin-password-123';

    private function createConfiguredAdmin(): User
    {
        config([
            'admin.email' => self::ADMIN_EMAIL,
            'admin.phone' => self::ADMIN_PHONE,
            'admin.password' => self::ADMIN_PASSWORD,
            'admin.name' => 'مدیر خانه گیلاسی',
        ]);

        return User::factory()->create([
            'name' => 'مدیر خانه گیلاسی',
            'email' => self::ADMIN_EMAIL,
            'phone' => self::ADMIN_PHONE,
            'password' => self::ADMIN_PASSWORD,
            'is_admin' => true,
        ]);
    }

    public function test_shared_admin_can_sign_in_with_configured_username_and_password(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('name="identity"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('admin.register');

        $this->post(route('admin.login.store'), [
            'identity' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_same_shared_admin_can_also_sign_in_with_configured_mobile_alias(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->post(route('admin.login.store'), [
            'identity' => '۰۹۱۲۰۸۳۲۱۷۹',
            'password' => self::ADMIN_PASSWORD,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_username_or_password_is_rejected_without_authenticating(): void
    {
        $this->createConfiguredAdmin();

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'identity' => self::ADMIN_EMAIL,
            'password' => 'wrong-password-value',
        ])->assertRedirect(route('admin.login'))
          ->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_non_admin_user_cannot_use_the_admin_login(): void
    {
        config([
            'admin.email' => self::ADMIN_EMAIL,
            'admin.phone' => self::ADMIN_PHONE,
        ]);

        User::factory()->create([
            'email' => self::ADMIN_EMAIL,
            'phone' => self::ADMIN_PHONE,
            'password' => self::ADMIN_PASSWORD,
            'is_admin' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'identity' => self::ADMIN_EMAIL,
            'password' => self::ADMIN_PASSWORD,
        ])->assertSessionHasErrors('identity');

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

        $admin = User::query()->where('email', self::ADMIN_EMAIL)->firstOrFail();

        $this->assertSame(self::ADMIN_PHONE, $admin->phone);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check(self::ADMIN_PASSWORD, $admin->password));
    }

    public function test_logout_clears_the_current_admin_session_and_protected_route_redirects_to_login(): void
    {
        $admin = $this->createConfiguredAdmin();

        $this->actingAs($admin)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_public_admin_registration_is_disabled_for_the_shared_account_flow(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', [])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
