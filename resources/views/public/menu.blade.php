<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ec">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="currency-label" content="{{ $settings->currency_label }}">
    <meta name="description" content="منوی به‌روز {{ $settings->name }}؛ محصولات، قیمت‌ها و افزودنی‌های فعال.">
    <title>منو و سفارش | {{ $settings->name }}</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site kg-menu-page" id="top">
    <div class="kg-topline"><span>🍒 منوی خانه گیلاسی</span><span>انتخاب روشن، سفارش ساده</span></div>
    @include('public.partials.nav')

    <main class="kg-menu-main" data-public-ordering data-menu-showcase data-order-endpoint="{{ route('public.orders.store') }}">
        <section class="kg-shop" aria-labelledby="kg-shop-title">
            <header class="kg-shop-intro">
                <div>
                    <span class="kg-eyebrow">از منوی امروز انتخاب کن</span>
                    <h1 id="kg-shop-title">طعم دلخواهت، <em>همین‌جاست.</em></h1>
                    <p>دسته‌بندی را انتخاب کن، تصویر واقعی غذاها و جزئیاتشان را ببین و همان‌جا به سبدت اضافه کن.</p>
                </div>
                <a class="kg-shop-cart-link" href="#online-cart">رفتن به سبد سفارش <span>↓</span></a>
            </header>

            @forelse($categories as $category)
                @if($loop->first)
                    <div class="kg-shop-layout">
                        <nav class="kg-shop-rail" aria-label="دسته‌بندی‌های منو">
                            @foreach($categories as $navCategory)
                                <button type="button"
                                        data-menu-category-select="{{ $navCategory->id }}"
                                        class="{{ $loop->first ? 'is-active' : '' }}"
                                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    <span>{{ $navCategory->name }}</span>
                                    <small>{{ $navCategory->products->count() }}</small>
                                </button>
                            @endforeach
                        </nav>

                        <div class="kg-shop-panels">
                @endif
                <section class="kg-shop-category-panel"
                         data-menu-category-panel="{{ $category->id }}"
                         aria-labelledby="kg-category-title-{{ $category->id }}"
                         @if(!$loop->first) hidden @endif>
                    <header class="kg-shop-category-heading">
                        <div>
                            <span class="kg-eyebrow">دسته‌بندی {{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 id="kg-category-title-{{ $category->id }}">{{ $category->name }}</h2>
                        </div>
                        <span>{{ $category->products->count() }} محصول</span>
                    </header>

                    <div class="kg-shop-featured">
                        <div class="kg-shop-feature-slot" data-menu-featured aria-live="polite"></div>
                    </div>

                    <div class="kg-shop-items" aria-label="محصولات این دسته">
                        @foreach($category->products as $product)
                            <button type="button"
                                    class="kg-shop-item {{ $loop->first ? 'is-active' : '' }}"
                                    data-menu-product-select
                                    data-menu-product-id="{{ $product->id }}"
                                    data-menu-index="{{ $loop->index }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                    @if($loop->index >= 4) hidden @endif>
                                <span class="kg-shop-item-image">
                                    @if($product->image_url)
                                        <img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                        <span data-menu-image-fallback hidden>🍒</span>
                                    @else
                                        <span data-menu-image-fallback>🍒</span>
                                    @endif
                                </span>
                                <span class="kg-shop-item-copy">
                                    <b>{{ $product->name }}</b>
                                    <small>{{ number_format($product->base_price) }} {{ $settings->currency_label }}</small>
                                </span>
                                <span class="kg-shop-item-arrow" aria-hidden="true">↗</span>
                            </button>
                        @endforeach
                    </div>

                    @if($category->products->count() > 4)
                        <nav class="kg-shop-pages" aria-label="صفحه‌بندی محصولات {{ $category->name }}">
                            @for($page = 0; $page < (int) ceil($category->products->count() / 4); $page++)
                                <button type="button"
                                        class="{{ $page === 0 ? 'is-active' : '' }}"
                                        data-menu-page-index="{{ $page }}"
                                        aria-label="صفحه {{ $page + 1 }}"
                                        aria-current="{{ $page === 0 ? 'page' : 'false' }}"></button>
                            @endfor
                        </nav>
                    @endif

                    <div class="kg-shop-template-bank" hidden>
                        @foreach($category->products as $product)
                            <template data-menu-product-template="{{ $product->id }}">
                                <article class="kg-shop-feature-product" data-product-card>
                                    <div class="kg-shop-feature-media">
                                        <div class="kg-shop-feature-photo">
                                            @if($product->image_url)
                                                <img data-menu-image src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="eager" decoding="async">
                                                <span data-menu-image-fallback hidden>🍒</span>
                                            @else
                                                <span data-menu-image-fallback>🍒</span>
                                            @endif
                                        </div>
                                        @if($product->preparation_time_minutes)
                                            <small class="kg-shop-prep-time">◷ {{ $product->preparation_time_minutes }} دقیقه آماده‌سازی</small>
                                        @endif
                                    </div>

                                    <div class="kg-shop-feature-info">
                                        <div class="kg-shop-feature-title">
                                            <div>
                                                <span class="kg-eyebrow">انتخاب امروز</span>
                                                <h3>{{ $product->name }}</h3>
                                            </div>
                                            <strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                                        </div>

                                        @if($product->description)
                                            <p class="kg-menu-description">{{ $product->description }}</p>
                                        @endif
                                        @if($product->ingredients)
                                            <div class="kg-menu-detail"><b>ترکیبات</b><span>{{ $product->ingredients }}</span></div>
                                        @endif
                                        @if(is_array($product->allergens) && count($product->allergens))
                                            <div class="kg-menu-allergens"><b>آلرژن‌های اعلام‌شده:</b> {{ implode('، ', $product->allergens) }}<small>در صورت حساسیت غذایی، پیش از سفارش با کارکنان هماهنگ کن.</small></div>
                                        @endif
                                        @if(is_array($product->dietary_tags) && count($product->dietary_tags))
                                            <div class="kg-menu-tags">
                                                @foreach($product->dietary_tags as $tag)<span>{{ $tag }}</span>@endforeach
                                            </div>
                                        @endif

                                        @if($product->options->isNotEmpty())
                                            <div class="kg-option-list">
                                                <span class="kg-option-title">سایز و افزودنی‌های اختیاری</span>
                                                @foreach($product->options as $option)
                                                    @php($optionType = $option->type instanceof \BackedEnum ? $option->type->value : (string)$option->type)
                                                    <label class="kg-option-row">
                                                        <input type="checkbox"
                                                               data-online-option="{{ $option->id }}"
                                                               data-option-type="{{ $optionType }}"
                                                               data-option-price="{{ (int) $option->price }}"
                                                               data-option-name="{{ $option->name }}">
                                                        <span>{{ $option->name }} <small>{{ $optionType === 'size' ? 'سایز' : 'افزودنی' }}</small></span>
                                                        <b>@if($option->price > 0)+{{ number_format($option->price) }}@else رایگان @endif</b>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="kg-add-row">
                                            <label class="kg-quantity">
                                                <span>تعداد</span>
                                                <input type="number" min="1" max="100" value="1" data-online-quantity aria-label="تعداد {{ $product->name }}">
                                            </label>
                                            <button type="button"
                                                    class="kg-button kg-button-dark"
                                                    data-add-online-product
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-price="{{ (int) $product->base_price }}">
                                                افزودن به سبد <span>＋</span>
                                            </button>
                                        </div>
                                        @if($product->calories !== null)
                                            <small class="kg-calories">{{ number_format($product->calories) }} کیلوکالری؛ بر اساس اطلاعات ثبت‌شده</small>
                                        @endif
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
                <div class="kg-empty-state kg-empty-catalog">
                    <span>🍒</span><b>منو در حال آماده‌شدن است.</b>
                    <p>وقتی دسته‌بندی و محصول فعال در پنل ثبت شود، این صفحه به‌صورت خودکار به‌روزرسانی می‌شود.</p>
                </div>
            @endforelse

            <p class="kg-catalog-note">ترکیبات، آلرژن‌ها و اطلاعات تغذیه‌ای فقط بر اساس اطلاعات واردشده توسط مجموعه نمایش داده می‌شوند.</p>
        </section>

        <section class="kg-cart-section" id="online-cart" aria-live="polite">
            <div class="kg-cart-heading">
                <span class="kg-eyebrow">سفارش بیرون‌بر</span>
                <h2>سبد انتخاب‌های تو</h2>
                <p>سفارش پس از ثبت در پنل مجموعه قرار می‌گیرد. این صفحه پرداخت آنلاین انجام نمی‌دهد؛ تسویه پس از هماهنگی با مجموعه و در صندوق یا هنگام تحویل ثبت می‌شود.</p>
            </div>
            <div class="kg-cart-layout">
                <div class="kg-cart-items-panel">
                    <div id="online-cart-empty" class="kg-cart-empty">هنوز محصولی به سبد اضافه نکرده‌ای.</div>
                    <div id="online-cart-items" class="kg-cart-items"></div>
                </div>
                <aside class="kg-checkout-card">
                    <div class="kg-cart-total">
                        <span>جمع تخمینی اقلام</span>
                        <b id="online-cart-total">۰ {{ $settings->currency_label }}</b>
                        <small>مالیات و خدمات احتمالی در محاسبه نهایی سرور لحاظ می‌شود.</small>
                    </div>
                    <form id="online-checkout-form" action="{{ route('public.orders.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::random(40) }}">
                        <label><span>نام و نام خانوادگی *</span><input name="customer_name" autocomplete="name" required maxlength="150" placeholder="نام شما"></label>
                        <label><span>شماره تماس *</span><input name="customer_phone" autocomplete="tel" inputmode="tel" required maxlength="30" placeholder="شماره موبایل"></label>
                        <label><span>توضیح سفارش</span><textarea name="customer_note" rows="2" maxlength="2000" placeholder="توضیح ضروری یا نکته‌ای درباره سفارش"></textarea></label>
                        <fieldset class="kg-payment-options">
                            <legend>ترجیح پرداخت</legend>
                            <label><input type="radio" name="payment_preference" value="at_counter" checked><span>هنگام تحویل / در صندوق</span></label>
                            <label><input type="radio" name="payment_preference" value="card_terminal"><span>کارت‌خوان</span></label>
                            <label><input type="radio" name="payment_preference" value="cash"><span>نقدی</span></label>
                        </fieldset>
                        <button type="submit" class="kg-button kg-button-primary kg-checkout-button" id="online-checkout-submit" disabled>ثبت سفارش بیرون‌بر <span>↗</span></button>
                        <p class="kg-form-message" id="online-checkout-message" role="status"></p>
                    </form>
                </aside>
            </div>
        </section>
    </main>

    @include('public.partials.footer')
</body>
</html>
