<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="{{ $homepageContent['hero_description'] ?? ('کافه و رستوران '.$settings->name) }}">
    <title>{{ $settings->name }} | کافه رستوران</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site page-home" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <main class="frame">
        <section class="slot hero" aria-labelledby="home-title">
            @if(!empty($media['hero']['url']))
                <img id="heroImg" src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: $settings->name }}" fetchpriority="high">
            @elseif($featuredProducts->first()?->image_url)
                <img id="heroImg" src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" fetchpriority="high">
            @else
                <img id="heroImg" alt="{{ $settings->name }}">
            @endif
            <header class="kg-public-nav" data-kg-header>
                <button class="burger" type="button" data-kg-menu-toggle aria-expanded="false" aria-controls="home-nav" aria-label="باز کردن منو"><i></i><i></i></button>
                <span class="brand">{{ $settings->name }}</span>
                <nav class="nav-links" id="home-nav" data-kg-nav aria-label="ناوبری اصلی">
                    <a href="{{ route('public.menu') }}">منو</a>
                    <a href="{{ route('home') }}#about">درباره ما</a>
                    <a href="{{ route('public.reservations.create') }}" class="cta">رزرو میز</a>
                </nav>
            </header>
            <h1 class="headline" id="home-title">{{ $homepageContent['hero_title_line1'] ?? 'طعمی مثل' }}<br>{{ $homepageContent['hero_title_line2'] ?? 'گیلاس' }}</h1>
            <div class="socials">
                @if(!empty($settings->instagram_url))
                    <a href="{{ $settings->instagram_url }}" target="_blank" rel="noopener noreferrer" aria-label="اینستاگرام">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/></svg>
                    </a>
                @endif
                @if(!empty($settings->telegram_url))
                    <a href="{{ $settings->telegram_url }}" target="_blank" rel="noopener noreferrer" aria-label="تلگرام">
                        <svg viewBox="0 0 24 24"><path d="M21 4 3 11l5 2 2 6 3-4 5 4z"/></svg>
                    </a>
                @endif
                @if(!empty($settings->phone))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}" aria-label="تماس با مجموعه">☎</a>
                @endif
            </div>
        </section>
        <aside class="side" aria-label="دسترسی سریع">
            <a href="{{ route('public.menu') }}" class="slot tile">
                @if(!empty($media['menu_cover']['url']))
                    <img src="{{ $media['menu_cover']['url'] }}" alt="{{ $media['menu_cover']['alt_text'] ?: 'منوی خانه گیلاسی' }}" loading="lazy">
                @elseif($featuredProducts->first()?->image_url)
                    <img src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i>منو و سفارش</div>
            </a>
            <a href="{{ route('public.reservations.create') }}" class="slot tile">
                @if(!empty($media['reservation_cover']['url']))
                    <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'رزرو میز' }}" loading="lazy">
                @elseif(!empty($media['gallery_1']['url']))
                    <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای رستوران' }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i>رزرو میز</div>
            </a>
            <a href="{{ !empty($settings->address) ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($settings->address) : route('public.menu') }}" class="slot tile" @if(!empty($settings->address)) target="_blank" rel="noopener noreferrer" @endif>
                @if(!empty($media['story_main']['url']))
                    <img src="{{ $media['story_main']['url'] }}" alt="{{ $media['story_main']['alt_text'] ?: 'رستوران خانه گیلاسی' }}" loading="lazy">
                @elseif(!empty($media['gallery_3']['url']))
                    <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?: 'رستوران خانه گیلاسی' }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i>رستوران ما</div>
            </a>
        </aside>
    </main>
</div>
</body>
</html>
