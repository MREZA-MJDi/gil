<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2a2722">
    <title>ثبت‌نام کارکنان | خانه گیلاسی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($loginBackground = \App\Models\SiteMediaAsset::urlFor('login_background', asset('images/login-background.webp')))
<body class="admin-auth-body" style="--login-image: url('{{ $loginBackground }}')">
    <main class="auth-layout">
        <section class="auth-form-panel" aria-labelledby="register-title">
            <header class="auth-form-heading">
                <span class="eyebrow">ثبت‌نام کارکنان با دعوت مدیر</span>
                <h1 id="register-title">ساخت حساب کارکنان</h1>
                <p>ثبت‌نام فقط با کلید دعوت مدیر مجموعه امکان‌پذیر است.</p>
            </header>
            @if ($errors->any()) <div class="auth-error" role="alert">{{ $errors->first() }}</div> @endif
            @unless($registrationEnabled)
                <div class="auth-info" role="status">ثبت‌نام هنوز فعال نشده است. مدیر باید مقدار امن <code>ADMIN_REGISTRATION_KEY</code> را در محیط سرور تنظیم کند.</div>
            @endunless
            <form method="POST" action="{{ route('admin.register.store') }}" class="auth-form">
                @csrf
                <label class="auth-label" for="name">نام و نام خانوادگی</label>
                <input class="auth-input" id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="150" required>
                <label class="auth-label" for="phone">شماره موبایل</label>
                <input class="auth-input" id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" dir="ltr" placeholder="0912 123 4567" maxlength="30" required>
                <label class="auth-label" for="password">رمز عبور</label>
                <input class="auth-input" id="password" type="password" name="password" autocomplete="new-password" minlength="14" required>
                <small class="auth-password-hint">حداقل ۱۴ نویسه انتخاب کن و از رمز تکراری استفاده نکن.</small>
                <label class="auth-label" for="password_confirmation">تکرار رمز عبور</label>
                <input class="auth-input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="14" required>
                <label class="auth-label" for="registration_key">کلید دعوت</label>
                <input class="auth-input" id="registration_key" type="password" name="registration_key" autocomplete="off" required>
                <button class="auth-submit" type="submit" @disabled(!$registrationEnabled)>ایجاد حساب و ورود <span aria-hidden="true">←</span></button>
            </form>
            <div class="auth-links"><a href="{{ route('admin.login') }}">بازگشت به ورود</a><span>ثبت‌نام با موبایل</span></div>
            <footer class="auth-legal">تا زمانی که سرویس پیامک OTP متصل نشده، مالکیت شماره موبایل با پیامک تأیید نمی‌شود.</footer>
        </section>
        <aside class="auth-brand-panel" aria-label="خانه گیلاسی">
            <div class="auth-brand-top"><span class="auth-mark">خ</span><span><b>خانه گیلاسی</b><small>کافه و رستوران</small></span></div>
            <div class="auth-brand-copy">
                <span class="auth-brand-kicker">محیط کاری امن</span>
                <h2>حساب‌های کاری، با دسترسی کنترل‌شده.</h2>
                <p>ثبت‌نام کارکنان با کلید دعوت انجام می‌شود؛ حساب‌های جدید بدون کلید امن ساخته نمی‌شوند.</p>
            </div>
            <div class="auth-brand-foot"><i></i><span>ثبت‌نام بسته به‌صورت پیش‌فرض</span></div>
        </aside>
    </main>
</body>
</html>
