<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ec">
    <title>درخواست دریافت شد | Q Food</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site kg-reservation-received" id="top">
    <div class="kg-topline"><span>🍒 Q Food</span><span>دریافت درخواست رزرو</span></div>
    @include('public.partials.nav')
    <main class="kg-received-main">
        <div class="kg-received-icon">✓</div>
        <span class="kg-eyebrow">درخواست دریافت شد</span>
        <h1>درخواستت به دست ما رسید.</h1>
        <p>اطلاعات در پنل مجموعه ثبت شد. وضعیت درخواست «در انتظار تأیید» است و این پیام به معنی قطعی‌شدن رزرو نیست؛ همکاران پس از بررسی ظرفیت با تو هماهنگ می‌کنند.</p>
        <div class="kg-received-actions">
            <a class="kg-button kg-button-primary" href="{{ route('home') }}">بازگشت به خانه <span>↗</span></a>
            <a class="kg-button kg-button-outline" href="{{ route('public.menu') }}">دیدن منو <span>↗</span></a>
        </div>
    </main>
    @include('public.partials.footer')
</body>
</html>
