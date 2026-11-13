<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="{{ $settings->name }}؛ منو و رزرو میز کافه رستوران خانه گیلاسی.">
    <title>{{ $settings->name ?: 'خانه گیلاسی' }} | کافه رستوران</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-home">
<div class="wrap">
    <main class="frame" aria-label="خانه خانه گیلاسی">
        @if(!empty($media['home_showcase_background']['url']))
            <div class="home-wallpaper" aria-hidden="true"><img src="{{ $media['home_showcase_background']['url'] }}" alt=""></div>
        @endif
        <section class="slot hero kg-reference-hero">
            @php($heroImage = $media['hero']['url'] ?? $media['home_hero']['url'] ?? $featuredProducts->first()?->image_url)
            @if($heroImage)<img src="{{ $heroImage }}" alt="{{ $media['hero']['alt_text'] ?? $settings->name }}" fetchpriority="high">@endif
            @include('public.partials.reference-nav')
            <span class="headline-kicker">{{ data_get($homepageContent, 'hero_eyebrow', 'کافه و رستوران خانه گیلاسی') }}</span>
            <h1 class="headline">{{ data_get($homepageContent, 'hero_title_line1', 'طعمی') }} {{ data_get($homepageContent, 'hero_title_line2', 'مثل گیلاس') }}</h1>
            <p class="headline-note">{{ data_get($homepageContent, 'hero_description', 'برای یک قهوه کوتاه، یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی؛ اینجا منتظر تو هستیم.') }}</p>
            <div class="home-hero-actions">
                <a href="{{ route('public.menu') }}">{{ data_get($homepageContent, 'hero_primary_cta', 'دیدن منو و سفارش') }}</a>
                <a href="{{ route('public.reservations.create') }}">{{ data_get($homepageContent, 'hero_secondary_cta', 'رزرو میز') }}</a>
            </div>
        </section>
        <aside class="side" aria-label="محصولات منتخب خانه گیلاسی">
            @forelse($featuredProducts->take(3) as $product)
                <a href="{{ route('public.menu') }}" class="slot tile kg-home-product-card">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <span class="kg-home-product-placeholder">🍒</span>
                    @endif
                    <span class="kg-home-product-copy">
                        <small>پیشنهاد خانه گیلاسی</small>
                        <b>{{ $product->name }}</b>
                        <strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                    </span>
                    <span class="kg-home-product-arrow" aria-hidden="true">←</span>
                </a>
            @empty
                <a href="{{ route('public.menu') }}" class="slot tile kg-home-product-card">
                    @if(!empty($media['menu_cover']['url']))<img src="{{ $media['menu_cover']['url'] }}" alt="منوی خانه گیلاسی" loading="lazy">@endif
                    <span class="kg-home-product-copy"><small>انتخاب اول</small><b>منوی خانه گیلاسی</b><strong>دیدن محصولات</strong></span>
                </a>
                <a href="{{ route('public.reservations.create') }}" class="slot tile kg-home-product-card">
                    @if(!empty($media['reservation_cover']['url']))<img src="{{ $media['reservation_cover']['url'] }}" alt="رزرو میز" loading="lazy">@endif
                    <span class="kg-home-product-copy"><small>برای دورهمی</small><b>رزرو میز</b><strong>ثبت درخواست</strong></span>
                </a>
                <a href="{{ route('public.menu') }}" class="slot tile kg-home-product-card">
                    @if(!empty($media['gallery_1']['url']))<img src="{{ $media['gallery_1']['url'] }}" alt="فضای خانه گیلاسی" loading="lazy">@endif
                    <span class="kg-home-product-copy"><small>تجربه خانه گیلاسی</small><b>طعم و حال خوب</b><strong>آشنایی با منو</strong></span>
                </a>
            @endforelse
        </aside>
    </main>

    <section class="kg-home-about" id="about">
        <div class="kg-home-about-copy">
            <span class="kg-section-kicker">خانه گیلاسی</span>
            <h2>برای لحظه‌هایی که ارزش مکث کردن دارند</h2>
            <p>{{ data_get($homepageContent, 'hero_description', 'از یک قهوه روزانه تا دورهمی‌های دوست‌داشتنی؛ منو را ببین، سفارش بده یا میزت را از قبل رزرو کن.') }}</p>
        </div>
        <div class="kg-home-about-actions">
            <a href="{{ route('public.menu') }}">مشاهده منو و سفارش</a>
            <a href="{{ route('public.reservations.create') }}">درخواست رزرو میز</a>
            @if($settings->phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">تماس با ما · {{ $settings->phone }}</a>
            @endif
        </div>
        @if($settings->address)
            <small class="kg-home-address">{{ $settings->address }}</small>
        @endif
    </section>
    <footer class="kg-public-footer">
        <span>{{ $settings->name ?: 'خانه گیلاسی' }}</span>
        <nav aria-label="پیوندهای پایین صفحه">
            <a href="{{ route('home') }}">خانه</a>
            <a href="{{ route('public.menu') }}">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو</a>
        </nav>
    </footer>
</div>
</body>
</html>
