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

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => PhoneNumber::normalize((string) $request->input('phone')),
        ]);

        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'phone.required' => 'شماره موبایل مدیر را وارد کن.',
            'phone.regex' => 'شماره موبایل معتبر وارد کن.',
        ]);

        $configuredPhone = PhoneNumber::normalize((string) config('admin.phone'));
        $submittedPhone = $credentials['phone'];

        // The only shared admin username is the phone configured in .env.
        $admin = $configuredPhone !== null && hash_equals($configuredPhone, $submittedPhone)
            ? User::query()->where('phone', $configuredPhone)->where('is_admin', true)->first()
            : null;

        if (! $admin || ! Hash::check($credentials['password'], (string) $admin->password)) {
            throw ValidationException::withMessages([
                'phone' => 'نام کاربری یا رمز عبور صحیح نیست.',
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
