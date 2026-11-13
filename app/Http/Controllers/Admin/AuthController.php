<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.login');
    }

    /**
     * All authorized reviewers use the same administrator username/password.
     * The username is ADMIN_PHONE, normalized to an international number.
     * Every browser/device receives its own Laravel session after login.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $configuredPhone = PhoneNumber::normalize((string) config('admin.phone'));
        $submittedPhone = PhoneNumber::normalize($credentials['username']);

        $admin = $configuredPhone !== null
            && $submittedPhone !== null
            && hash_equals($configuredPhone, $submittedPhone)
                ? User::query()
                    ->where('phone', $configuredPhone)
                    ->where('is_admin', true)
                    ->first()
                : null;

        if (! $admin || ! Hash::check($credentials['password'], (string) $admin->password)) {
            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور صحیح نیست.',
            ]);
        }

        Auth::guard('web')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
