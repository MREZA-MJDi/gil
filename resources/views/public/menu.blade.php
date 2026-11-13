<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="description" content="منوی به‌روز {{ $settings->name }}؛ محصولات، قیمت‌ها و افزودنی‌های فعال.">
    <title>منو و سفارش | {{ $settings->name }}</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-menu" id="top">
<div class="m">
    <header class="top" data-kg-header>
        <button class="burger2" type="button" data-kg-menu-toggle aria-expanded="false" aria-controls="menu-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}">{{ $settings->name }}</a>
        <nav class="menu-nav" id="menu-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a>
            <a href="{{ route('public.menu') }}" aria-current="page">منو</a>
            <a href="{{ route('home') }}#about">درباره ما</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
        </nav>
        <div class="tools"><a href="#online-cart" aria-label="سبد سفارش"><span></span></a></div>
    </header>

    <main class="menu-layout" data-public-ordering data-menu-showcase data-order-endpoint="{{ route('public.orders.store') }}">
        @if($categories->isNotEmpty())
            <nav class="cats" aria-label="دسته‌بندی‌های منو">
                @foreach($categories as $category)
                    <button type="button" data-menu-category-select="{{ $category->id }}" class="{{ $loop->first ? 'is-active' : '' }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $category->name }}</button>
                @endforeach
            </nav>

            @foreach($categories as $category)
                <section class="category-panel" data-menu-category-panel="{{ $category->id }}" aria-label="{{ $category->name }}" @if(!$loop->first) hidden @endif>
                    <div class="stage" data-menu-featured aria-live="polite"></div>
                    <div class="cards" aria-label="محصولات {{ $category->name }}">
                        @foreach($category->products as $product)
                            <button type="button" class="card {{ $loop->first ? 'is-active' : '' }}" data-menu-product-select data-menu-product-id="{{ $product->id }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" @if($loop->index >= 4) hidden @endif>
                                @if($product->image_url)
                                    <img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                    <span data-menu-image-fallback hidden>🍒</span>
                                @else
                                    <span data-menu-image-fallback>🍒</span>
                                @endif
                                <span class="p">{{ number_format($product->base_price) }}</span>
                                <span class="n">{{ $product->name }}</span>
                                <span class="s">{{ $product->preparation_time_minutes ? $product->preparation_time_minutes.' دقیقه آماده‌سازی' : ($product->dietary_tags[0] ?? 'پیشنهاد خانه گیلاسی') }}</span>
                            </button>
                        @endforeach
                    </div>
                    @if($category->products->count() > 4)
                        <div class="dots" aria-label="صفحه‌بندی محصولات">
                            @for($page = 0; $page < (int) ceil($category->products->count() / 4); $page++)
                                <button type="button" data-menu-page-index="{{ $page }}" class="{{ $page === 0 ? 'is-active' : '' }}" aria-label="صفحه {{ $page + 1 }}" aria-current="{{ $page === 0 ? 'page' : 'false' }}"></button>
                            @endfor
                        </div>
                    @endif
                    <div hidden>
                        @foreach($category->products as $product)
                            <template data-menu-product-template="{{ $product->id }}">
                                <div class="plate">
                                    @if($product->image_url)
                                        <img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="eager" decoding="async">
                                        <span data-menu-image-fallback hidden>🍒</span>
                                    @else
                                        <span data-menu-image-fallback>🍒</span>
                                    @endif
                                </div>
                                <div class="info" data-product-card>
                                    <h2>{{ $product->name }}</h2>
                                    <p>{{ $product->description ?: 'با مواد اولیه تازه و با دقت در خانه گیلاسی آماده می‌شود.' }}</p>
                                    @if($product->ingredients)
                                        <p><strong>ترکیبات:</strong> {{ $product->ingredients }}</p>
                                    @endif
                                    @if(is_array($product->allergens) && count($product->allergens))
                                        <p class="product-extra"><strong>آلرژن‌های اعلام‌شده:</strong> {{ implode('، ', $product->allergens) }}</p>
                                    @endif
                                    @if(is_array($product->dietary_tags) && count($product->dietary_tags))
                                        <p class="product-extra">{{ implode(' · ', $product->dietary_tags) }}</p>
                                    @endif
                                    @if($product->options->isNotEmpty())
                                        <div class="product-extra">
                                            @foreach($product->options as $option)
                                                @php($optionType = $option->type instanceof \BackedEnum ? $option->type->value : (string)$option->type)
                                                <label class="kg-option-row">
                                                    <input type="checkbox" data-online-option="{{ $option->id }}" data-option-type="{{ $optionType }}" data-option-price="{{ (int) $option->price }}" data-option-name="{{ $option->name }}">
                                                    <span>{{ $option->name }} <small>{{ $optionType === 'size' ? 'سایز' : 'افزودنی' }}</small></span>
                                                    <b>@if($option->price > 0)+{{ number_format($option->price) }}@else رایگان @endif</b>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="buy">
                                        <div class="price">{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></div>
                                        <label class="kg-quantity"><input type="number" min="1" max="100" value="1" data-online-quantity aria-label="تعداد {{ $product->name }}"></label>
                                        <button type="button" class="order" data-add-online-product data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ (int) $product->base_price }}">سفارش دهید +</button>
                                    </div>
                                    @if($product->calories !== null)
                                        <small class="product-extra">{{ number_format($product->calories) }} کیلوکالری (بر اساس اطلاعات ثبت‌شده)</small>
                                    @endif
                                </div>
                            </template>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @else
            <div class="kg-empty-state"><span>🍒</span><b>منو در حال تکمیل است.</b><p>با ثبت دسته‌بندی و محصولات فعال در پنل، منو به‌صورت خودکار نمایش داده می‌شود.</p></div>
        @endif
    </main>

    <section class="kg-menu-cart" id="online-cart" aria-live="polite">
        <h2>سبد سفارش</h2>
        <p>قیمت نهایی و موجودی هنگام ثبت سفارش در سرور دوباره بررسی می‌شود. پرداخت آنلاین در این مرحله انجام نمی‌شود.</p>
        <div class="kg-cart-layout">
            <div class="kg-cart-items-panel">
                <div id="online-cart-empty" class="kg-cart-empty">هنوز محصولی به سبد اضافه نکرده‌ای.</div>
                <div id="online-cart-items" class="kg-cart-items"></div>
            </div>
            <aside class="kg-checkout-card">
                <div class="kg-cart-total"><span>جمع تخمینی اقلام</span><b id="online-cart-total">۰ {{ $settings->currency_label }}</b><small>مالیات و خدمات احتمالی در محاسبه نهایی سرور لحاظ می‌شود.</small></div>
                <form id="online-checkout-form" action="{{ route('public.orders.store') }}" method="POST">
                    @csrf
                    <label><span>نام و نام خانوادگی *</span><input name="customer_name" autocomplete="name" required maxlength="150" placeholder="نام شما"></label>
                    <label><span>شماره تماس *</span><input name="customer_phone" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="شماره موبایل"></label>
                    <label><span>توضیح سفارش</span><textarea name="customer_note" rows="2" maxlength="2000" placeholder="توضیح ضروری یا نکته‌ای درباره سفارش"></textarea></label>
                    <fieldset class="kg-payment-options">
                        <legend>ترجیح پرداخت</legend>
                        <label><input type="radio" name="payment_preference" value="at_counter" checked><span>هنگام تحویل / در صندوق</span></label>
                        <label><input type="radio" name="payment_preference" value="card_terminal"><span>کارت‌خوان</span></label>
                        <label><input type="radio" name="payment_preference" value="cash"><span>نقدی</span></label>
                    </fieldset>
                    <button type="submit" class="kg-checkout-button" id="online-checkout-submit" disabled>ثبت سفارش بیرون‌بر ↗</button>
                    <p class="kg-form-message" id="online-checkout-message" role="status"></p>
                </form>
            </aside>
        </div>
    </section>
</div>
</body>
</html>
