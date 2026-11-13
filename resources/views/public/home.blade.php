<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0f11">
    <meta name="description" content="{{ $settings->name }}؛ منو، سفارش بیرون‌بر و درخواست رزرو میز.">
    <title>{{ $settings->name }} | کافه رستوران</title>
    @vite(['resources/css/public-reference.css', 'resources/js/public-site.js'])
</head>
<body class="page-home" id="top">
<div class="wrap">
    <div class="logo">{{ $settings->name }}</div>
    <div class="frame kg-reference-hero" data-home-carousel>
        <div class="slot hero">
            @if(!empty($media['hero']['url']))
                <img src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: $settings->name }}" fetchpriority="high">
            @elseif($featuredProducts->first()?->image_url)
                <img src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" fetchpriority="high">
            @endif

            @include('public.partials.reference-nav')

            <span class="home-eyebrow">{{ $homepageContent['hero_eyebrow'] ?? 'کافه و رستوران' }}</span>
            <h1 class="headline">
                {{ $homepageContent['hero_title_line1'] ?? 'طعم خوب،' }}
                @if(!empty($homepageContent['hero_title_line2']))<br>{{ $homepageContent['hero_title_line2'] }}@endif
            </h1>
            <p class="home-hero-description">{{ $homepageContent['hero_description'] ?? 'برای یک قهوه کوتاه، یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی؛ منتظرت هستیم.' }}</p>
            <div class="home-hero-actions">
                <a href="{{ route('public.menu') }}">دیدن منو <span>↗</span></a>
                <a href="{{ route('public.reservations.create') }}">رزرو میز <span>↗</span></a>
            </div>
        </div>
        <div class="side" data-home-carousel-stage>
            <a href="{{ route('public.menu') }}" class="slot tile" data-home-carousel-card data-home-index="0">
                @if(!empty($media['menu_cover']['url']))
                    <img src="{{ $media['menu_cover']['url'] }}" alt="{{ $media['menu_cover']['alt_text'] ?: 'منوی خانه گیلاسی' }}" loading="lazy">
                @elseif($featuredProducts->get(0)?->image_url)
                    <img src="{{ $featuredProducts->get(0)->image_url }}" alt="{{ $featuredProducts->get(0)->name }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i><span>منو و انتخاب غذا</span></div>
            </a>
            <a href="{{ route('public.reservations.create') }}" class="slot tile" data-home-carousel-card data-home-index="1">
                @if(!empty($media['reservation_cover']['url']))
                    <img src="{{ $media['reservation_cover']['url'] }}" alt="{{ $media['reservation_cover']['alt_text'] ?: 'رزرو میز' }}" loading="lazy">
                @elseif(!empty($media['gallery_1']['url']))
                    <img src="{{ $media['gallery_1']['url'] }}" alt="{{ $media['gallery_1']['alt_text'] ?: 'فضای کافه' }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i><span>رزرو میز</span></div>
            </a>
            <a href="#about" class="slot tile" data-home-carousel-card data-home-index="2">
                @if(!empty($media['story_main']['url']))
                    <img src="{{ $media['story_main']['url'] }}" alt="{{ $media['story_main']['alt_text'] ?: 'رستوران ما' }}" loading="lazy">
                @elseif($featuredProducts->get(1)?->image_url)
                    <img src="{{ $featuredProducts->get(1)->image_url }}" alt="{{ $featuredProducts->get(1)->name }}" loading="lazy">
                @elseif(!empty($media['gallery_2']['url']))
                    <img src="{{ $media['gallery_2']['url'] }}" alt="{{ $media['gallery_2']['alt_text'] ?: 'رستوران ما' }}" loading="lazy">
                @endif
                <div class="tag"><i>←</i><span>رستوران ما</span></div>
            </a>
        </div>
    </div>

    <section class="home-showcase" id="about"
        @if(!empty($media['home_showcase_background']['url'])) style="--showcase-bg:url('{{ $media['home_showcase_background']['url'] }}')" @endif>
        <header>
            <span>{{ $homepageContent['showcase_eyebrow'] ?? 'انتخاب‌های خانه گیلاسی' }}</span>
            <h2>{{ $homepageContent['showcase_title'] ?? 'انتخاب‌های تازه' }}</h2>
            <p>{{ $homepageContent['showcase_description'] ?? 'محصولات فعال و تصاویر واقعی ثبت‌شده در پنل مدیریت.' }}</p>
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
                        <b>{{ $category->name }}</b><small>{{ number_format($category->available_products_count) }} محصول در منو</small>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if(!empty($media['home_reservation_banner']['url']))
        <section class="home-reservation-banner">
            <img src="{{ $media['home_reservation_banner']['url'] }}" alt="{{ $media['home_reservation_banner']['alt_text'] ?: 'بنر رزرو' }}" loading="lazy">
            <div><span>تولد · دورهمی · قرار دوستانه</span><h2>برای یک خاطره خوب، میز بچین.</h2><a href="{{ route('public.reservations.create') }}">درخواست رزرو ↗</a></div>
        </section>
    @endif

    @if(collect(['gallery_1','gallery_2','gallery_3'])->contains(fn ($slot) => !empty($media[$slot]['url'])))
        <section class="home-photo-gallery">
            <header><span>نگاهی به خانه گیلاسی</span><h2>فضای ما، از نزدیک</h2></header>
            <div>
                @foreach(['gallery_1'=>'فضای کافه','gallery_2'=>'غذا و نوشیدنی','gallery_3'=>'مناسبت‌ها و دورهمی'] as $slot=>$label)
                    @if(!empty($media[$slot]['url']))
                        <figure><img src="{{ $media[$slot]['url'] }}" alt="{{ $media[$slot]['alt_text'] ?: $label }}" loading="lazy"><figcaption>{{ $label }}</figcaption></figure>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <footer class="home-access">
        <span>{{ $settings->name }}</span>
        <div>
            <a href="{{ route('public.menu') }}">منو</a><a href="{{ route('public.reservations.create') }}">رزرو میز</a>
            @guest
                <a href="{{ route('customer.login') }}">ورود مشتری</a>
                <a href="{{ route('admin.login') }}">ورود کارکنان</a>
            @else
                @can('access-customer')
                    <a href="{{ route('customer.dashboard') }}">حساب من</a>
                @endcan
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">خروج</button></form>
                @endcan
            @endguest
        </div>
    </footer>
</div>
</body>
</html>