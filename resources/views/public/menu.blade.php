<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4efe6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="description" content="منوی به‌روز {{ $settings->name }} با قیمت‌ها، افزودنی‌ها و جزئیات محصولات.">
    <title>منو | {{ $settings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-menu-page">
    <div class="site-topline"><span>هر انتخاب خوب، از شناختن طعم‌ها شروع می‌شود.</span><span class="site-topline-right"><i></i> منوی به‌روز کافه</span></div>
    @include('public.partials.nav')
    <main data-public-ordering>
        @include('public.partials.menu.hero')
        @include('public.partials.menu.category-jumpbar')
        @include('public.partials.menu.catalog')
        @include('public.partials.menu.online-order-panel')
        @include('public.partials.menu.cta')
    </main>
    @include('public.partials.footer')
</body>
</html>