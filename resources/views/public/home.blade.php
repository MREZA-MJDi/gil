<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="{{ $settings->name }}؛ منو و رزرو میز کافه رستوران خانه گیلاسی.">
    <title>{{ $settings->name ?: 'خانه گیلاسی' }} | کافه رستوران</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-home">
<div class="wrap">
    <div class="logo">{{ $settings->name ?: 'خانه گیلاسی' }}</div>
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
            @if($featuredProducts->first())
                <p class="headline-note">پیشنهاد امروز: {{ $featuredProducts->first()->name }}</p>
            @endif
            <p class="sr-only">{{ data_get($homepageContent, 'hero_description', '') }}</p>
            <div class="socials">
                @if($settings->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}" aria-label="تماس با خانه گیلاسی">☎</a>@endif
                <a href="{{ route('public.menu') }}" aria-label="مشاهده منو"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></a>
            </div>
        </section>
        <aside class="side" aria-label="دسترسی سریع">
            <a href="{{ route('public.menu') }}" class="slot tile">
                @if(!empty($media['menu_cover']['url']))<img src="{{ $media['menu_cover']['url'] }}" alt="منوی خانه گیلاسی" loading="lazy">@elseif($featuredProducts->first()?->image_url)<img src="{{ $featuredProducts->first()->image_url }}" alt="محصول منتخب" loading="lazy">@endif
                <span class="tag"><i>←</i>{{ data_get($homepageContent, 'hero_primary_cta', 'منو') }}</span>
            </a>
            <a href="{{ route('public.reservations.create') }}" class="slot tile">
                @if(!empty($media['home_reservation_banner']['url']))<img src="{{ $media['home_reservation_banner']['url'] }}" alt="{{ $media['home_reservation_banner']['alt_text'] ?: 'رزرو میز' }}" loading="lazy">@elseif(!empty($media['reservation_cover']['url']))<img src="{{ $media['reservation_cover']['url'] }}" alt="رزرو میز" loading="lazy">@elseif(!empty($media['gallery_1']['url']))<img src="{{ $media['gallery_1']['url'] }}" alt="فضای رستوران" loading="lazy">@endif
                <span class="tag"><i>←</i>{{ data_get($homepageContent, 'hero_secondary_cta', 'رزرو میز') }}</span>
            </a>
            <a href="#about" class="slot tile">
                @if(!empty($media['gallery_1']['url']))<img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای خانه گیلاسی' }}" loading="lazy">@elseif(!empty($media['story_main']['url']))<img src="{{ $media['story_main']['url'] }}" alt="رستوران خانه گیلاسی" loading="lazy">@elseif(!empty($media['gallery_2']['url']))<img src="{{ $media['gallery_2']['url'] }}" alt="رستوران خانه گیلاسی" loading="lazy">@endif
                <span class="tag"><i>←</i>رستوران ما</span>
            </a>
        </aside>
    </main>
    <span id="about" class="sr-only">خانه گیلاسی؛ {{ $settings->address }}</span>
</div>
</body>
</html>