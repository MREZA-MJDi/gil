<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>منوی میز {{ $table->name }} | {{ $settings->name }}</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site kg-qr-page page-menu" id="top">
<div class="m">
    <header class="top" data-kg-header>
        <button class="burger2" type="button" data-kg-menu-toggle aria-expanded="false" aria-controls="qr-menu-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a href="{{ route('home') }}" class="brand">{{ $settings->name }}</a>
        <nav class="menu-nav" id="qr-menu-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a>
            <a href="{{ route('public.menu') }}">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
        </nav>
        <span class="kg-table-badge">میز {{ $table->name }}</span>
    </header>
    <main class="kg-qr-main menu-layout" id="customer-menu-app"
          data-customer-qr-menu
          data-table-name="{{ $table->name }}"
          data-data-url="{{ route('customer.menu.data', ['token' => request()->route('token')]) }}"
          data-order-url="{{ route('customer.menu.orders.store', ['token' => request()->route('token')]) }}">
        <div class="kg-qr-loading" role="status">در حال دریافت منوی به‌روز…</div>
    </main>
</div>
<footer class="kg-qr-footer">سفارش برای میز {{ $table->name }} ثبت می‌شود. پرداخت پس از ثبت سفارش و از طریق صندوق یا کارت‌خوان انجام می‌شود.</footer>
</body>
</html>
