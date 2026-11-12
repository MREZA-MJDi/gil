<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <meta name="currency-label" content="{{ \\App\\Models\\RestaurantSetting::current()->currency_label }}">
    <title>{{ config('app.name', 'خانه گیلاسی') }} | داشبورد مدیریت</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
    <div id="app" data-page="dashboard"></div>
</body>
</html>
