<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.login');
    }

    /**
     * Authenticate the one shared administrator account configured in .env.
     * The configured email is the canonical username; the configured phone is
     * accepted as an optional alias for the same account.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identity = trim($credentials['identity']);
        $adminEmail = Str::lower(trim((string) config('admin.email')));
        $adminPhone = PhoneNumber::normalize((string) config('admin.phone'));

        $matchesConfiguredIdentity = false;

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $matchesConfiguredIdentity = $adminEmail !== ''
                && hash_equals($adminEmail, Str::lower($identity));
        } else {
            $submittedPhone = PhoneNumber::normalize($identity);
            $matchesConfiguredIdentity = $submittedPhone !== null
                && $adminPhone !== null
                && hash_equals($adminPhone, $submittedPhone);
        }

        $admin = $matchesConfiguredIdentity
            ? User::query()
                ->where('email', $adminEmail)
                ->where('is_admin', true)
                ->first()
            : null;

        if (! $admin || ! Hash::check($credentials['password'], (string) $admin->password)) {
            throw ValidationException::withMessages([
                'identity' => 'نام کاربری یا رمز عبور صحیح نیست.',
            ]);
        }

        Auth::login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
