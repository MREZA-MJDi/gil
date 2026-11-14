<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="theme-color" content="#ffffff"><title>منوی میز {{ $table->name }} | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-menu page-qr" id="top">
<div class="m">
    <header class="top menu-top" data-kg-header>
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-qr-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}">
            @php($brandLogo = \App\Models\SiteMediaAsset::urlFor('brand_logo'))
            @if($brandLogo)<img src="{{ $brandLogo }}" alt="{{ $settings->name }}" style="width:40px;height:40px;object-fit:contain;border-radius:50%">@else<span aria-hidden="true">{{ \Illuminate\Support\Str::substr($settings->name, 0, 1) }}</span>@endif
            <span>{{ $settings->name }}</span>
        </a>
        <nav class="menu-nav" id="kg-qr-nav" data-kg-nav aria-label="ناوبری اصلی"><a href="{{ route('home') }}">خانه</a><a href="{{ route('public.menu') }}">منوی بیرون‌بر</a><a href="{{ route('public.reservations.create') }}">رزرو میز</a></nav>
        @include('public.partials.profile-link')
        <span class="kg-table-badge">میز {{ $table->name }}</span>
    </header>
    <main class="kg-qr-main">
        <div class="kg-qr-intro"><span>منوی دیجیتال {{ $settings->name }}</span><h1>از میز خودت<br><em>سفارش بده.</em></h1><p>محصولات را انتخاب کن و سفارش را برای همین میز ثبت کن.</p></div>
        <div id="customer-menu-app" data-customer-qr-menu data-table-name="{{ $table->name }}"
             data-data-url="{{ route('customer.menu.data', ['token'=>request()->route('token')]) }}"
             data-order-url="{{ route('customer.menu.orders.store', ['token'=>request()->route('token')]) }}">
            <div class="kg-qr-loading" role="status">در حال دریافت منوی به‌روز…</div>
        </div>
    </main>
    <footer class="kg-qr-footer">برای ثبت سفارش، کارکنان مجموعه در جریان قرار می‌گیرند؛ پرداخت پس از ثبت و از طریق صندوق یا کارت‌خوان انجام می‌شود.</footer>
</div>
</body>
</html>