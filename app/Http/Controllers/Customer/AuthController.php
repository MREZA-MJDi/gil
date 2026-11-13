<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSetting;
use App\Models\SiteMediaAsset;
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
    public function login(): View { return view('customer.auth.login', $this->viewData()); }
    public function register(): View { return view('customer.auth.register', $this->viewData()); }

    private function viewData(): array
    {
        $media = SiteMediaAsset::query()->get()->mapWithKeys(fn (SiteMediaAsset $asset) => [
            $asset->slot => ['url' => $asset->url, 'alt_text' => $asset->alt_text],
        ])->all();

        return ['settings' => RestaurantSetting::current(), 'media' => $media];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize((string) $request->input('phone'))]);
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['sometimes', 'boolean'],
        ], ['phone.required' => 'شماره موبایل را وارد کن.', 'phone.regex' => 'شماره موبایل معتبر وارد کن.']);

        if (! Auth::guard('web')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
            'is_admin' => false,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['phone' => 'شماره موبایل یا رمز عبور صحیح نیست.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'));
    }

    public function registerStore(Request $request): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize((string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:14', 'max:200', 'confirmed'],
        ], [
            'phone.regex' => 'شماره موبایل را به‌درستی وارد کن.',
            'phone.unique' => 'با این شماره موبایل قبلاً حساب ساخته شده است؛ وارد حساب خودت شو.',
            'email.unique' => 'این ایمیل قبلاً استفاده شده است.',
            'password.min' => 'رمز عبور باید حداقل ۱۴ نویسه باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ]);

        $user = User::query()->create([
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'email' => filled($data['email'] ?? null) ? trim($data['email']) : null,
            'password' => $data['password'],
            'is_admin' => false,
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
