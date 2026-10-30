<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#191919">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <title>منوی دیجیتال {{ $settings->name }} · میز {{ $table->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="customer-menu-page">
    <main class="customer-menu-wrap">
        <header class="customer-menu-header">
            <div><span class="eyebrow">منوی دیجیتال خانه گیلاسی</span><h1>{{ $settings->name }}</h1><p>انتخاب، افزودن به سبد و ثبت سفارش از میز شما</p></div>
            <span class="table-chip">میز {{ $table->name }}</span>
        </header>
        <div id="customer-menu-app" data-token="{{ request()->route('token') }}"></div>
    </main>
</body>
</html>
