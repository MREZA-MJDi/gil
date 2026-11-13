<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2a2722">
    <title>ورود مدیر | خانه گیلاسی</title>
    @vite(['resources/css/auth-entry.css'])
</head>
@php($loginBackground = \App\Models\SiteMediaAsset::urlFor('login_background', asset('images/login-background.webp')))
<body class="admin-auth-body" style="--login-image: url('{{ $loginBackground }}')">
    <main class="auth-layout">
        <section class="auth-form-panel" aria-labelledby="login-title">
            <header class="auth-form-heading">
                <span class="eyebrow">دسترسی مدیریت</span>
                <h1 id="login-title">خوش برگشتی</h1>
                <p>برای ورود، شماره موبایل مدیر و رمز عبور مشترک مجموعه را وارد کن.</p>
            </header>
            @if ($errors->any()) <div class="auth-error" role="alert">{{ $errors->first() }}</div> @endif
            <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form">
                @csrf
                <label class="auth-label" for="username">نام کاربری (شماره موبایل)</label>
                <div class="auth-input-wrap">
                    <input class="auth-input" id="username" type="tel" name="username" value="{{ old('username') }}"
                        autocomplete="username" inputmode="tel" dir="ltr"
                        placeholder="شماره موبایل مدیر" maxlength="30" required autofocus>
                </div>
                <label class="auth-label" for="password">رمز عبور</label>
                <input class="auth-input" id="password" type="password" name="password" autocomplete="current-password"
                    placeholder="رمز عبور مشترک مجموعه" required>
                <label class="auth-check-line"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
                <button class="auth-submit" type="submit">ورود امن به داشبورد <span aria-hidden="true">←</span></button>
            </form>
            <footer class="auth-legal">همه افراد مجاز از همین حساب مشترک استفاده می‌کنند؛ نشست هر مرورگر و دستگاه جداگانه است.</footer>
        </section>
        <aside class="auth-brand-panel" aria-label="خانه گیلاسی">
            <div class="auth-brand-top"><span class="auth-mark">خ</span><span><b>خانه گیلاسی</b><small>کافه و رستوران</small></span></div>
            <div class="auth-brand-copy">
                <span class="auth-brand-kicker">تمام عملیات، در یک پنل</span>
                <h2>از سفارش تا صندوق، همه‌چیز مرتب.</h2>
                <p>یک پنل یکپارچه برای پیگیری سفارش‌ها، پرداخت‌ها، موجودی، رزروها و گزارش‌های روزانه.</p>
            </div>
            <div class="auth-brand-foot"><i></i><span>ورود مشترک · نشست مستقل برای هر دستگاه</span></div>
        </aside>
    </main>
</body>
</html>
