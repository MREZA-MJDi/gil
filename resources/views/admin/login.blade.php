<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2a2722">
    <title>ورود مدیر | خانه گیلاسی</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($loginBackground = \App\Models\SiteMediaAsset::urlFor('login_background', asset('images/login-background.webp')))
<body class="admin-auth-body" style="--login-image: url('{{ $loginBackground }}')">
    <main class="auth-layout">
        <section class="auth-form-panel" aria-labelledby="login-title">
            <header class="auth-form-heading">
                <span class="eyebrow">دسترسی امن کارکنان</span>
                <h1 id="login-title">خوش برگشتی</h1>
                <p>برای مدیریت سفارش‌ها، صندوق و موجودی با شماره موبایلت وارد شو.</p>
            </header>
            @if ($errors->any()) <div class="auth-error" role="alert">{{ $errors->first() }}</div> @endif
            <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form">
                @csrf
                <label class="auth-label" for="phone">شماره موبایل</label>
                <div class="auth-input-wrap">
                    <input class="auth-input" id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                        autocomplete="tel" inputmode="tel" dir="ltr" placeholder="0912 123 4567" maxlength="30" required autofocus>
                </div>
                <label class="auth-label" for="password">رمز عبور</label>
                <input class="auth-input" id="password" type="password" name="password" autocomplete="current-password"
                    placeholder="رمز عبور حساب" required>
                <label class="auth-check-line"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
                <button class="auth-submit" type="submit">ورود امن به داشبورد <span aria-hidden="true">←</span></button>
            </form>
            <div class="auth-links"><span>حساب کاربری نداری؟</span><a href="{{ route('admin.register') }}">ثبت‌نام با کلید دعوت</a></div>
            <footer class="auth-legal">شماره موبایل شناسه ورود شماست. اطلاعات ورود را در اختیار دیگران قرار ندهید.</footer>
        </section>
        <aside class="auth-brand-panel" aria-label="خانه گیلاسی">
            <div class="auth-brand-top"><span class="auth-mark">خ</span><span><b>خانه گیلاسی</b><small>کافه و رستوران</small></span></div>
            <div class="auth-brand-copy">
                <span class="auth-brand-kicker">OPERATIONS, IN ONE PLACE</span>
                <h2>از سفارش تا صندوق، همه‌چیز مرتب.</h2>
                <p>یک پنل یکپارچه برای پیگیری سفارش‌ها، پرداخت‌ها، موجودی، رزروها و گزارش‌های روزانه.</p>
            </div>
            <div class="auth-brand-foot"><i></i><span>دسترسی کارکنان · ثبت رویدادهای عملیاتی</span></div>
        </aside>
    </main>
</body>
</html>
