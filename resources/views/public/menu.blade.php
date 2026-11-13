<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="منوی فعال {{ $settings->name }}؛ تصویر، قیمت و جزئیات محصولات از پنل مدیریت.">
    <title>منو | {{ $settings->name }}</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-menu" id="top">
<div class="m" data-public-ordering data-menu-showcase data-order-endpoint="{{ route('public.orders.store') }}">
    <div class="top menu-top" data-kg-header>
        <button type="button" class="burger2" data-kg-menu-toggle aria-expanded="false" aria-label="باز کردن منو"><i></i><i></i></button>
        <a class="brand" href="{{ route('home') }}">
            @if(!empty($media['brand_logo']['url']))<img class="menu-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="{{ $media['brand_logo']['alt_text'] ?: $settings->name }}">@endif
            <span>{{ $settings->name }}</span>
        </a>
        <nav class="menu-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}">خانه</a><a href="{{ route('public.menu') }}" class="act" aria-current="page">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
            @guest<a href="{{ route('customer.login') }}">ورود مشتری</a>@else @can('access-customer')<a href="{{ route('customer.dashboard') }}">حساب من</a>@endcan @endguest
        </nav>
        <div class="tools"><span></span></div>
    </div>

    @if(!empty($media['menu_cover']['url']))
        <section class="kg-menu-cover">
            <img src="{{ $media['menu_cover']['url'] }}" alt="{{ $media['menu_cover']['alt_text'] ?: 'تصویر منو' }}">
            <span>منوی {{ $settings->name }}</span>
        </section>
    @endif

    <header class="menu-intro"><span>منوی روز</span><h1>طعم دلخواهت را انتخاب کن</h1><p>محصولات و تصویرها مستقیماً از منوی فعال مدیریت دریافت می‌شوند.</p></header>

    @forelse($categories as $category)
        @if($loop->first)
            <div class="stage menu-layout">
                <nav class="cats" aria-label="دسته‌بندی‌ها">
                    @foreach($categories as $navCategory)
                        <button type="button" class="{{ $loop->first ? 'act' : '' }}" data-menu-category-select="{{ $navCategory->id }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $navCategory->name }}</button>
                    @endforeach
                </nav>
                <div class="menu-panels">
        @endif
            <section class="menu-category-panel" id="category-{{ $category->id }}" data-menu-category-panel="{{ $category->id }}" @if(!$loop->first) hidden @endif>
                <div class="stage-feature-host" data-menu-featured aria-live="polite"><div class="stage-empty">یکی از غذاها را برای دیدن جزئیات انتخاب کن.</div></div>
                <div class="cards">
                    @foreach($category->products as $product)
                        <button type="button" class="card {{ $loop->first ? 'act' : '' }}" data-menu-product-select data-menu-product-id="{{ $product->id }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" @if($loop->index >= 4) hidden @endif>
                            @if($product->image_url)<img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"><span data-menu-image-fallback hidden>تصویر در دسترس نیست</span>
                            @else<span data-menu-image-fallback>تصویر ثبت نشده</span>@endif
                            <span class="p">{{ number_format($product->base_price) }} {{ $settings->currency_label }}</span>
                            <span class="n">{{ $product->name }}</span>
                            <span class="s">{{ $product->preparation_time_minutes ? $product->preparation_time_minutes.' دقیقه آماده‌سازی' : 'پیشنهاد مجموعه' }}</span>
                        </button>
                    @endforeach
                </div>
                @if($category->products->count() > 4)
                    <div class="dots">
                        @for($page=0; $page < (int) ceil($category->products->count()/4); $page++)
                            <button type="button" data-menu-page-index="{{ $page }}" class="{{ $page===0 ? 'act':'' }}" aria-label="صفحه {{ $page+1 }}"></button>
                        @endfor
                    </div>
                @endif
                <div class="menu-template-bank" hidden>
                    @foreach($category->products as $product)
                        <template data-menu-product-template="{{ $product->id }}">
                            <article class="stage-feature">
                                <div class="plate">
                                    @if($product->image_url)<img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="eager"><span data-menu-image-fallback hidden>تصویر در دسترس نیست</span>
                                    @else<span data-menu-image-fallback>تصویر ثبت نشده</span>@endif
                                </div>
                                <div class="info">
                                    <h2>{{ $product->name }}</h2>
                                    <p>{{ $product->description ?: 'این محصول از منوی فعال مجموعه قابل سفارش است.' }}</p>
                                    @if($product->ingredients)<div class="product-detail"><b>ترکیبات</b><span>{{ $product->ingredients }}</span></div>@endif
                                    @if(is_array($product->allergens) && count($product->allergens))<div class="product-detail"><b>آلرژن‌ها</b><span>{{ implode('، ', $product->allergens) }}</span></div>@endif
                                    @if(is_array($product->dietary_tags) && count($product->dietary_tags))<div class="product-tags">@foreach($product->dietary_tags as $tag)<span>{{ $tag }}</span>@endforeach</div>@endif
                                    @if($product->options->isNotEmpty())
                                        <div class="product-options"><b>سایز و افزودنی‌ها</b>
                                            @foreach($product->options as $option)
                                                @php($optionType=$option->type instanceof \BackedEnum ? $option->type->value : (string)$option->type)
                                                <label><input type="checkbox" data-online-option="{{ $option->id }}" data-option-type="{{ $optionType }}" data-option-price="{{ (int)$option->price }}" data-option-name="{{ $option->name }}"><span>{{ $option->name }}</span><small>@if($option->price>0)+{{ number_format($option->price) }}@else رایگان @endif</small></label>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="buy">
                                        <div class="price">{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></div>
                                        <label class="product-quantity">تعداد<input type="number" min="1" max="100" value="1" data-online-quantity aria-label="تعداد {{ $product->name }}"></label>
                                        <button type="button" class="order" data-add-online-product data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ (int)$product->base_price }}">سفارش دهید</button>
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
        <div class="menu-empty"><b>منو در حال تکمیل است.</b><p>پس از فعال‌کردن دسته‌بندی و محصول در مدیریت، این صفحه به‌صورت خودکار به‌روز می‌شود.</p></div>
    @endforelse

    <section class="order-cart" id="online-cart">
        <div class="cart-heading"><span>سفارش بیرون‌بر</span><h2>سبد انتخاب تو</h2><p>مبلغ نهایی در سرور محاسبه می‌شود.</p></div>
        <div id="online-cart-empty" class="cart-empty">هنوز محصولی به سبد اضافه نکرده‌ای.</div>
        <div id="online-cart-items" class="cart-items" aria-live="polite"></div>
        <div class="cart-total"><span>جمع تقریبی</span><strong id="online-cart-total">۰ {{ $settings->currency_label }}</strong></div>
        <form id="online-checkout-form" action="{{ route('public.orders.store') }}" method="POST">
            @csrf
            <div class="cart-fields">
                <label>نام و نام خانوادگی<input name="customer_name" maxlength="150" autocomplete="name" required></label>
                <label>شماره تماس<input name="customer_phone" inputmode="tel" autocomplete="tel" maxlength="30" required></label>
                <label class="cart-field-wide">توضیحات سفارش<textarea name="customer_note" rows="2" maxlength="1000"></textarea></label>
                <label>روش پرداخت<select name="payment_preference"><option value="at_counter">پرداخت هنگام دریافت</option><option value="online">پرداخت آنلاین (در صورت فعال بودن)</option></select></label>
            </div>
            <button type="submit" id="online-checkout-submit" disabled>ثبت سفارش بیرون‌بر ↗</button>
            <p id="online-checkout-message" role="status" aria-live="polite"></p>
        </form>
    </section>
</div>
</body>
</html>