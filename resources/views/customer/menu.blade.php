<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#191614">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="description" content="منوی دیجیتال {{ $settings->name }}؛ مشاهده محصولات، قیمت‌های به‌روز و ثبت سفارش مستقیم از میز.">
    <title>منوی دیجیتال | {{ $settings->name }} · میز {{ $table->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="public-site customer-menu-page">
    <main class="gil-menu-page-shell">
        <div
            id="customer-menu-app"
            data-token="{{ request()->route('token') }}"
            aria-live="polite"
        >
            <div class="gil-menu-loading" role="status">
                <span class="gil-loading-mark" aria-hidden="true">گ</span>
                <span>در حال آماده‌سازی منوی میز {{ $table->name }}…</span>
            </div>
        </div>
    </main>
    @include('public.partials.traveling-cup')
</body>
</html>
