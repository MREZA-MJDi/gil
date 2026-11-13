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
<body class="public-site page-qr-menu" id="top">
<div class="menu-shell">
    <header class="menu-top" data-kg-header>
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-qr-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}">{{ $settings->name ?: 'خانه گیلاسی' }}</a>
        <span class="table-pill">میز {{ $table->name }}</span>
        <nav class="menu-nav" id="kg-qr-nav" data-kg-nav aria-label="ناوبری">
            <a href="{{ route('home') }}">خانه</a>
            <a href="{{ route('public.menu') }}">منو و سفارش بیرون‌بر</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
            @if($settings->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">تماس با ما</a>@endif
        </nav>
    </header>
    <p class="menu-intro">طعم دلخواهت، همین‌جاست.</p>

    <main data-customer-qr-menu
          data-table-name="{{ $table->name }}"
          data-data-url="{{ route('customer.menu.data', ['token' => request()->route('token')]) }}"
          data-order-url="{{ route('customer.menu.orders.store', ['token' => request()->route('token')]) }}">
        <section class="menu-stage" aria-label="منوی دیجیتال میز">
            <nav class="menu-cats" data-qr-categories aria-label="دسته‌بندی‌های منو"></nav>
            <div class="menu-panels">
                <div class="menu-featured-slot" data-qr-feature aria-live="polite"><div class="menu-empty">در حال دریافت منوی به‌روز…</div></div>
                <div class="menu-cards" data-qr-cards></div>
            </div>
        </section>
        <section class="menu-cart" id="qr-order-cart" aria-live="polite">
            <h2>سفارش میز {{ $table->name }}</h2>
            <div class="menu-cart-layout">
                <div class="menu-cart-lines" id="kg-qr-cart-lines"><div class="menu-empty">هنوز محصولی انتخاب نشده است.</div></div>
                <div>
                    <div class="menu-cart-total">جمع تخمینی: <b id="kg-qr-total">۰ {{ $settings->currency_label }}</b></div>
                    <form id="kg-qr-order-form">
                        <input name="customer_name" maxlength="150" placeholder="نام شما (اختیاری)" autocomplete="name">
                        <input name="customer_phone" maxlength="30" inputmode="tel" placeholder="شماره تماس (اختیاری)" autocomplete="tel">
                        <button type="submit" id="kg-qr-submit" disabled>ثبت سفارش برای این میز</button>
                        <p class="menu-status" id="kg-qr-status" role="status"></p>
                    </form>
                </div>
            </div>
        </section>
    </main>
    <p class="menu-qr-note">سفارش به آشپزخانه و صندوق مجموعه ارسال می‌شود. پرداخت از طریق صندوق یا کارت‌خوان انجام می‌شود.</p>
    @include('public.partials.footer')
</div>
</body>
</html>