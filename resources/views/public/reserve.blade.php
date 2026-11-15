<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171614">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>رزرو، تولد و مشاوره | {{ $settings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-booking-v2">
    <div class="site-topline"><span>قرار خوب، با برنامه‌ریزی بهتر شروع می‌شود.</span><span class="site-topline-right"><i></i> رزرو و مناسبت‌ها</span></div>
    @include('public.partials.nav')
    <main class="reserve-page section-wrap">
        @include('public.partials.reservation.intro')
        @include('public.partials.reservation.form')
    </main>
    @include('public.partials.footer')
</body>
</html>
