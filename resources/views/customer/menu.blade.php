<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf6ec">
    <title>منوی میز {{ $table->name }} | {{ $settings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site kg-qr-page" id="top">
    <header class="kg-qr-header">
        <a href="{{ route('home') }}" class="kg-brand"><span class="kg-brand-mark">🍒</span><span class="kg-brand-copy"><strong>{{ $settings->name }}</strong><small>منوی دیجیتال</small></span></a>
        <span class="kg-table-badge">میز {{ $table->name }}</span>
    </header>
    <main class="kg-qr-main">
        <div class="kg-qr-intro"><span class="kg-eyebrow">خوش آمدی</span><h1>از میز خودت<br><em>سفارش بده.</em></h1><p>محصولات را انتخاب کن و سفارش را برای همین میز ثبت کن.</p></div>
        <div id="customer-menu-app"
             data-customer-qr-menu
             data-table-name="{{ $table->name }}"
             data-data-url="{{ route('customer.menu.data', ['token' => request()->route('token')]) }}"
             data-order-url="{{ route('customer.menu.orders.store', ['token' => request()->route('token')]) }}">
            <div class="kg-qr-loading" role="status">در حال دریافت منوی به‌روز…</div>
        </div>
    </main>
    <footer class="kg-qr-footer">برای ثبت سفارش، کارکنان مجموعه در جریان قرار می‌گیرند؛ پرداخت پس از ثبت و از طریق صندوق یا کارت‌خوان انجام می‌شود.</footer>
</body>
</html>
