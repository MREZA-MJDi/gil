<!DOCTYPE html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>درخواست ثبت شد | {{ $settings->name }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="public-site public-booking-v2"><div class="site-topline"><span>{{ $settings->name }} · رزرو و مراسم</span><span class="site-topline-right"><i></i> دریافت درخواست</span></div>
@include('public.partials.nav')
<main class="reserve-success section-wrap"><div class="reserve-success-mark">✓</div><span class="section-kicker">درخواست دریافت شد</span><h1>درخواستت به دست ما رسید.</h1><p>درخواست در پنل مجموعه ثبت می‌شود. این پیام به معنی قطعی‌شدن رزرو نیست؛ همکاران بعد از بررسی ظرفیت و جزئیات با تو تماس می‌گیرند.</p><div class="reserve-success-actions"><a href="{{ route('home') }}" class="button button-dark">بازگشت به خانه <span>↗</span></a><a href="{{ route('public.menu') }}" class="button button-cream">دیدن منو <span>↗</span></a></div></main>
@include('public.partials.footer')</body></html>
