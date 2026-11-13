<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="theme-color" content="#ffffff">
    <title>منو | {{ $settings->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-menu">
<div class="menu-shell" data-public-ordering data-menu-showcase data-order-endpoint="{{ route('public.orders.store') }}">
    <header class="menu-top" data-kg-header>
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-menu-nav" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}">{{ $settings->name ?: 'خانه گیلاسی' }}</a>
        <nav class="menu-nav" id="kg-menu-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a><a href="{{ route('public.menu') }}" aria-current="page">منو</a>
            <a href="{{ route('home') }}#about">درباره ما</a><a href="{{ route('public.reservations.create') }}">رزرو میز</a>
        </nav>
    </header>

    @forelse($categories as $category)
        @if($loop->first)
            <div class="menu-stage">
                <nav class="menu-cats" aria-label="دسته‌بندی غذاها">
                    @foreach($categories as $navCategory)
                        <button type="button" data-menu-category-select="{{ $navCategory->id }}" class="{{ $loop->first ? 'is-active' : '' }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $navCategory->name }}</button>
                    @endforeach
                </nav>
                <div class="menu-panels">
        @endif
        <section class="menu-panel" data-menu-category-panel="{{ $category->id }}" @if(!$loop->first) hidden @endif>
            <div class="menu-featured-slot" data-menu-featured aria-live="polite"></div>
            <div class="menu-cards" aria-label="محصولات {{ $category->name }}">
                @foreach($category->products as $product)
                    <button type="button" class="menu-card {{ $loop->first ? 'is-active' : '' }}" data-menu-product-select data-menu-product-id="{{ $product->id }}" data-menu-index="{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" @if($loop->index >= 4) hidden @endif>
                        @if($product->image_url)<img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"><span data-menu-image-fallback hidden>🍒</span>@else<span class="menu-card-placeholder">🍒</span>@endif
                        <span class="card-price">{{ number_format($product->base_price) }}</span>
                        <span class="card-name">{{ $product->name }}</span><span class="card-sub">{{ $product->preparation_time_minutes ? $product->preparation_time_minutes.' دقیقه آماده‌سازی' : 'پیشنهاد خانه گیلاسی' }}</span>
                    </button>
                @endforeach
            </div>
            @if($category->products->count() > 4)
                <div class="menu-dots" aria-label="صفحه محصولات">
                    @for($page=0;$page<(int)ceil($category->products->count()/4);$page++)
                        <button type="button" data-menu-page-index="{{ $page }}" class="{{ $page===0?'is-active':'' }}" aria-label="صفحه {{ $page+1 }}"></button>
                    @endfor
                </div>
            @endif
            <div hidden>
                @foreach($category->products as $product)
                    <template data-menu-product-template="{{ $product->id }}">
                        <article class="menu-featured" data-product-card>
                            <div class="menu-plate">
                                @if($product->image_url)<img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="eager"><span data-menu-image-fallback hidden>🍒</span>@else<div class="menu-plate-fallback">🍒</div>@endif
                            </div>
                            <div class="menu-info">
                                <span class="menu-kicker">{{ $category->name }}</span>
                                <h2>{{ $product->name }}</h2>
                                <p>{{ $product->description ?: 'با عشق و مواد اولیه تازه در خانه گیلاسی آماده می‌شود.' }}</p>
                                @if($product->ingredients)<small class="menu-ingredients">ترکیبات: {{ $product->ingredients }}</small>@endif
                                @if(is_array($product->allergens) && count($product->allergens))<small class="menu-ingredients">آلرژن‌ها: {{ implode('، ', $product->allergens) }}</small>@endif
                                @if($product->options->isNotEmpty())
                                    <div class="menu-options">
                                        @foreach($product->options as $option)
                                            @php($optionType=$option->type instanceof \BackedEnum?$option->type->value:(string)$option->type)
                                            <label><input type="checkbox" data-online-option="{{ $option->id }}" data-option-type="{{ $optionType }}" data-option-price="{{ (int)$option->price }}" data-option-name="{{ $option->name }}"> {{ $option->name }} <small>{{ $option->price>0?'+'.number_format($option->price):'رایگان' }}</small></label>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="menu-buy">
                                    <div class="menu-price">{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></div>
                                    <label class="menu-quantity">تعداد <input type="number" min="1" max="100" value="1" data-online-quantity></label>
                                    <button type="button" class="menu-order" data-add-online-product data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ (int)$product->base_price }}">سفارش دهید +</button>
                                </div>
                            </div>
                        </article>
                    </template>
                @endforeach
            </div>
        </section>
        @if($loop->last)
                </div>
            </div>
        @endif
    @empty
        <div class="menu-empty">هنوز محصول فعالی در منو ثبت نشده است.</div>
    @endforelse

    <section class="menu-cart" id="online-cart" aria-live="polite">
        <h2>سبد سفارش</h2>
        <div class="menu-cart-layout">
            <div><div id="online-cart-empty" class="menu-empty">هنوز محصولی انتخاب نکرده‌ای.</div><div id="online-cart-items" class="menu-cart-lines"></div></div>
            <form id="online-checkout-form" action="{{ route('public.orders.store') }}" method="POST">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::random(40) }}">
                <label>نام و نام خانوادگی<input name="customer_name" required maxlength="150" autocomplete="name"></label>
                <label>شماره تماس<input name="customer_phone" required maxlength="30" inputmode="tel" autocomplete="tel"></label>
                <label>توضیحات سفارش<textarea name="customer_note" rows="2" maxlength="2000"></textarea></label>
                <input type="hidden" name="payment_preference" value="at_counter">
                <div class="menu-cart-total">جمع تخمینی: <b id="online-cart-total">۰ {{ $settings->currency_label }}</b></div>
                <button type="submit" id="online-checkout-submit" disabled>ثبت سفارش بیرون‌بر</button>
                <p class="menu-status" id="online-checkout-message" role="status"></p>
            </form>
        </div>
    </section>
</div>
</body>
</html>