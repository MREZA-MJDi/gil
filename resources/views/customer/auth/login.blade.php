<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <title>ورود به حساب | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-reserve account-auth-page">
<div class="wrap">
    @include('public.partials.site-logo')
    <section class="frame account-auth-frame">
        <div class="form account-auth-form">
            <span class="account-eyebrow">حساب کاربری</span>
            <h2>خوش برگشتی</h2>
            <p class="sub">برای ادامه، شماره موبایل و رمز عبورت را وارد کن.</p>
            @if($errors->any())<div class="form-errors" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('customer.login.store') }}">
                @csrf
                <label><span>شماره موبایل</span><input type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" autocomplete="username" dir="ltr" placeholder="۰۹۱۲۰۰۰۰۰۰۰" required></label>
                <label><span>رمز عبور</span><input type="password" name="password" autocomplete="current-password" placeholder="رمز عبور حساب" required></label>
                <label class="remember-line"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
                <button class="submit" type="submit">ورود به حساب </button>
            </form>
            <p class="account-switch">حساب نداری؟ <a href="{{ route('customer.register') }}">ثبت‌نام کن</a></p>
            <a class="account-back" href="{{ route('home') }}">بازگشت به سایت</a>
        </div>
        <div class="slot hero account-auth-art">
            @if(!empty($media['login_background']['url']))
                <img src="{{ $media['login_background']['url'] }}" alt="{{ $media['login_background']['alt_text'] ?: 'خانه گیلاسی' }}">
            @elseif(!empty($media['hero']['url']))
                <img src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: 'خانه گیلاسی' }}">
            @endif
            <div class="account-art-copy"><span>همه رزروها، یک‌جا</span><h2>قرار بعدی‌ات<br>نزدیک است.</h2><p>درخواست رزرو ثبت کن و نتیجه بررسی مجموعه را از حساب خودت دنبال کن.</p></div>
        </div>
    </section>
</div>
</body>
</html>