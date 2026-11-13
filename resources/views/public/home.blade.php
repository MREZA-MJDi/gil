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

            <div class="home-hero-actions">
                <a href="{{ route('public.menu') }}">دیدن منو </a>
                <a href="{{ route('public.reservations.create') }}">رزرو میز </a>
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

    <section class="best-food" id="bestFood" data-best-food-carousel aria-labelledby="bestFoodTitle">
        <header class="best-food__header">
            <span class="best-food__eyebrow">{{ $homepageContent['showcase_eyebrow'] ?? 'از منوی فعال مجموعه' }}</span>
            <h2 class="best-food__title" id="bestFoodTitle">
                @if(($homepageContent['showcase_title'] ?? 'بهترین طعم‌ها برای تو') !== 'بهترین طعم‌ها برای تو')
                    {{ $homepageContent['showcase_title'] }}
                @else
                    پیشنهادهای <span>خوشمزه</span> امروز
                @endif
            </h2>
            <span class="best-food__line" aria-hidden="true"></span>
            <p class="best-food__desc">{{ $homepageContent['showcase_description'] ?? 'غذاها و نوشیدنی‌هایی که همین حالا در منوی فعال آماده انتخاب‌اند؛ اسلایدها را ورق بزن و انتخابت را پیدا کن.' }}</p>
        </header>

        @if($featuredProducts->isNotEmpty())
            <div class="fc" data-best-food-track aria-roledescription="carousel" aria-label="محصولات منتخب منو" tabindex="0">
                @foreach($featuredProducts as $product)
                    <button
                        class="fc__slide"
                        type="button"
                        data-best-food-slide
                        data-index="{{ $loop->index }}"
                        data-name="{{ $product->name }}"
                        data-description="{{ $product->description ?: 'برای دیدن جزئیات و ثبت سفارش، محصول را در منوی کامل باز کن.' }}"
                        data-url="{{ route('public.menu') }}#category-{{ $product->category_id }}"
                        aria-label="نمایش {{ $product->name }}"
                        aria-pressed="false"
                    >
                        <span class="fc__img">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            @else
                                <span class="fc__image-fallback">تصویر این محصول ثبت نشده</span>
                            @endif
                        </span>
                        <span class="fc__caption">
                            <strong>{{ $product->name }}</strong>
                            <small>{{ number_format($product->base_price) }} {{ $settings->currency_label }}</small>
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="best-food__controls">
                <button class="fc__arrow" type="button" data-best-food-prev aria-label="محصول قبلی">‹</button>
                <div class="fc__dots" data-best-food-dots aria-label="انتخاب محصول">
                    @foreach($featuredProducts as $product)
                        <button class="fc__dot" type="button" data-best-food-dot="{{ $loop->index }}" aria-label="نمایش {{ $product->name }}" aria-pressed="false"></button>
                    @endforeach
                </div>
                <button class="fc__arrow" type="button" data-best-food-next aria-label="محصول بعدی">›</button>
            </div>

            <div class="best-food__selected" aria-live="polite">
                <p data-best-food-description>{{ $featuredProducts->first()->description ?: 'برای دیدن جزئیات و ثبت سفارش، محصول را در منوی کامل باز کن.' }}</p>
                <a data-best-food-link href="{{ route('public.menu') }}#category-{{ $featuredProducts->first()->category_id }}">مشاهده در منوی کامل</a>
            </div>
        @else
            <div class="best-food__empty">هنوز محصول فعالی در منو ثبت نشده است. پس از افزودن محصول از مدیریت، این بخش خودکار به‌روز می‌شود.</div>
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
            <div><span>تولد · دورهمی · قرار دوستانه</span><h2>برای یک خاطره خوب، میز بچین.</h2><a href="{{ route('public.reservations.create') }}">درخواست رزرو </a></div>
        </section>
    @endif

    @if(collect(['gallery_1','gallery_2','gallery_3'])->contains(fn ($slot) => !empty($media[$slot]['url'])))
        <section class="home-photo-gallery kg-photo-gallery">
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
            <a href="{{ route('public.menu') }}">منو</a>
            <a href="{{ route('public.reservations.create') }}">رزرو میز</a>
        </div>
    </footer>
</div>
</body>
</html>