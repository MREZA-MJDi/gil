<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.login');
    }

    public function register(): View
    {
        return view('admin.register', ['registrationEnabled' => filled(config('admin.registration_key'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize((string) $request->input('phone'))]);
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt(['phone' => $credentials['phone'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['phone' => 'شماره موبایل یا رمز عبور صحیح نیست.']);
        }

        $request->session()->regenerate();

        if (! $request->user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['phone' => 'این حساب اجازهٔ ورود به پنل مدیریت را ندارد.']);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize((string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:14', 'max:200', 'confirmed'],
            'registration_key' => ['required', 'string', 'max:256'],
        ], [
            'phone.regex' => 'شماره موبایل را با کد کشور وارد کنید؛ نمونه: 09121234567',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۱۴ نویسه باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ]);

        $expectedKey = (string) config('admin.registration_key', '');
        if ($expectedKey === '' || ! hash_equals($expectedKey, (string) $data['registration_key'])) {
            throw ValidationException::withMessages(['registration_key' => 'ثبت‌نام عمومی فعال نیست یا کلید دعوت معتبر نیست.']);
        }

        $user = User::query()->create([
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'email' => null,
            'password' => $data['password'],
            'is_admin' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
