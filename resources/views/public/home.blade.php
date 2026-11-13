<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="{{ $settings->name }}؛ منو، سفارش بیرون‌بر و درخواست رزرو میز.">
    <!-- Legacy resource entry resources/css/public.css is retained for independent QR Menu and reservation receipt pages; this page loads public-reference.css only. -->
    <title>{{ $settings->name }} | کافه و رستوران</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-home" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>

    <section class="frame kg-reference-hero" data-home-carousel aria-label="خانه گیلاسی">
        <div class="slot hero">
            @if(!empty($media['hero']['url']))
                <img src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: $settings->name }}" fetchpriority="high">
            @elseif($featuredProducts->first()?->image_url)
                <img src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" fetchpriority="high">
            @endif

            @include('public.partials.nav')

            <div class="hero-copy">
                <span class="hero-eyebrow">{{ $homepageContent['hero_eyebrow'] ?? 'کافه و رستوران' }}</span>
                <h1 class="headline">{{ $homepageContent['hero_title_line1'] ?? 'طعمی مثل گیلاس' }}<br><em>{{ $homepageContent['hero_title_line2'] ?? '' }}</em></h1>
                <p>{{ $homepageContent['hero_description'] ?? 'برای یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی، منتظرت هستیم.' }}</p>
                <div class="hero-actions">
                    <a href="{{ route('public.menu') }}" class="hero-action-primary">{{ $homepageContent['hero_primary_cta'] ?? 'دیدن منو' }} <span>↗</span></a>
                    <a href="{{ route('public.reservations.create') }}" class="hero-action-secondary">{{ $homepageContent['hero_secondary_cta'] ?? 'رزرو میز' }}</a>
                </div>
            </div>

            <div class="home-carousel-controls" aria-label="محصولات منتخب">
                <button type="button" data-home-prev aria-label="محصول قبلی">→</button>
                <div class="home-carousel-dots">
                    @foreach($featuredProducts->take(3) as $product)
                        <button type="button" data-home-dot="{{ $loop->index }}" aria-label="نمایش {{ $product->name }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="{{ $loop->first ? 'is-active' : '' }}"></button>
                    @endforeach
                </div>
                <button type="button" data-home-next aria-label="محصول بعدی">←</button>
            </div>
        </div>

        <aside class="side" data-home-carousel-stage aria-label="محصولات منتخب">
            @forelse($featuredProducts->take(3) as $product)
                <a href="{{ route('public.menu') }}#category-{{ $product->category_id }}" class="slot tile" data-home-carousel-card aria-label="مشاهده {{ $product->name }}">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    @endif
                    <div class="tag"><i>←</i><span>{{ $product->name }}</span></div>
                </a>
            @empty
                <a href="{{ route('public.menu') }}" class="slot tile tile-empty"><div class="tag"><i>←</i><span>دیدن منو</span></div></a>
                <a href="{{ route('public.reservations.create') }}" class="slot tile tile-empty"><div class="tag"><i>←</i><span>رزرو میز</span></div></a>
                <a href="#about" class="slot tile tile-empty"><div class="tag"><i>←</i><span>درباره ما</span></div></a>
            @endforelse
        </aside>
    </section>

    <section class="home-showcase" id="home-showcase" @if(!empty($media['home_showcase_background']['url'])) style="--showcase-bg:url('{{ $media['home_showcase_background']['url'] }}')" @endif>
        <header>
            <span>{{ $homepageContent['showcase_eyebrow'] ?? 'انتخاب‌های تازه' }}</span>
            <h2>{{ $homepageContent['showcase_title'] ?? 'انتخاب‌های تازه' }}</h2>
            <p>{{ $homepageContent['showcase_description'] ?? 'محصولات فعال و تصویرهای واقعی ثبت‌شده در پنل مدیریت.' }}</p>
        </header>
        @if($featuredProducts->isNotEmpty())
            <div class="home-showcase-products">
                @foreach($featuredProducts as $product)
                    <a href="{{ route('public.menu') }}#category-{{ $product->category_id }}" class="home-showcase-product">
                        @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">@endif
                        <span><b>{{ $product->name }}</b><strong>{{ number_format($product->base_price) }} {{ $settings->currency_label }}</strong></span>
                    </a>
                @endforeach
            </div>
        @endif
        @if($categories->isNotEmpty())
            <div class="home-category-counts">
                @foreach($categories as $category)
                    <a href="{{ route('public.menu') }}#category-{{ $category->id }}">
                        <b>{{ $category->name }}</b>
                        <small>{{ number_format($category->available_products_count) }} محصول در منو</small>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if(!empty($media['home_reservation_banner']['url']))
        <section class="home-reservation-banner">
            <img src="{{ $media['home_reservation_banner']['url'] }}" alt="{{ $media['home_reservation_banner']['alt_text'] ?: 'تصویر بنر رزرو' }}" loading="lazy">
            <div><span>تولد · دورهمی · قرار دوستانه</span><h2>برای یک خاطره خوب، میز بچین.</h2><a href="{{ route('public.reservations.create') }}">درخواست رزرو ↗</a></div>
        </section>
    @endif

    @if(collect(['gallery_1', 'gallery_2', 'gallery_3'])->contains(fn ($slot) => !empty($media[$slot]['url'])))
        <section class="kg-photo-gallery home-photo-gallery">
            <header><span>نگاهی به خانه گیلاسی</span><h2>فضای ما، از نزدیک</h2></header>
            <div>
                @foreach(['gallery_1' => 'فضای کافه', 'gallery_2' => 'غذا و نوشیدنی', 'gallery_3' => 'مناسبت‌ها و دورهمی'] as $slot => $label)
                    @if(!empty($media[$slot]['url']))
                        <figure><img src="{{ $media[$slot]['url'] }}" alt="{{ $media[$slot]['alt_text'] ?: $label }}" loading="lazy"><figcaption>{{ $label }}</figcaption></figure>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="home-note" id="about">
        <span>خانه گیلاسی</span>
        <p>طعم خوب و منوی واقعی و به‌روز مجموعه را ببین، محصول دلخواهت را انتخاب کن یا برای یک قرار خوب درخواست رزرو بفرست.</p>
        <div><a href="{{ route('public.menu') }}">منو و سفارش ↗</a><a href="{{ route('public.reservations.create') }}">درخواست رزرو ↗</a>
            @guest<a href="{{ route('admin.login') }}">ورود کارکنان</a>@endguest
            @auth
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">خروج</button></form>
                @endcan
            @endauth
        </div>
    </section>
</div>
</body>
</html>