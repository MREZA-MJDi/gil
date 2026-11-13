<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11"><title>درخواست رزرو دریافت شد | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-reserve page-reservation-received" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <div class="frame">
        <div class="form received-form">
            <span class="received-check">✓</span><h2>درخواستت به دست ما رسید.</h2>
            <p class="sub">درخواست رزرو در پنل مجموعه ثبت شده و اکنون در انتظار بررسی است. تأیید نهایی را از حساب مشتری خودت پیگیری کن.</p>
            <a class="submit" href="{{ route('customer.dashboard') }}">مشاهده وضعیت رزرو </a>
            <a class="received-link" href="{{ route('public.menu') }}">دیدن منو</a>
        </div>
        <div class="slot hero reserve-art">
            @if(!empty($media['reservation_cover']['url']))<img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'فضای رزرو' }}">@elseif(!empty($media['gallery_1']['url']))<img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای کافه' }}">@endif
            @include('public.partials.reference-nav')
        </div>
    </div>
</div>
</body>
</html>