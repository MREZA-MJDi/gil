<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>منوی {{ $settings->name }} · {{ $table->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="customer-menu-page">
    <main class="customer-menu-wrap">
        <header class="customer-menu-header">
            <div><span class="eyebrow">KHANE GILASI / MENU</span><h1>{{ $settings->name }}</h1><p>منوی میز {{ $table->name }}</p></div>
            <span class="table-chip">میز {{ $table->name }}</span>
        </header>
        <div id="customer-menu-app" data-token="{{ request()->route('token') }}"></div>
    </main>
</body>
</html>
