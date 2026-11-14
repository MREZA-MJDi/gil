<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171313">
    <meta name="description" content="{{ $settings->name }}؛ منو، سفارش بیرون‌بر و درخواست رزرو میز و مناسبت‌ها.">
    <title>{{ $settings->name }} | کافه و رستوران</title>
    @vite(['resources/css/public.css', 'resources/js/public-site.js'])
</head>
<body class="public-site kg-home" id="top">
    <div class="kg-topline">
        <span>🍒 یک مکث کوچک برای حال خوب</span>
        <span>طعم‌ها را ببین، لحظه‌ات را انتخاب کن</span>
    </div>
    @include('public.partials.nav')

    <main>
        <section class="kg-hero kg-reference-hero" aria-labelledby="kg-home-title">
            <div class="kg-hero-brand">
                <span><b>Q Food</b><small>کافه و رستوران</small></span>
            </div>
            <div class="kg-hero-follow"><span>انتخاب تازه‌ی امروز</span><i></i><i></i><i></i></div>

            <div class="kg-hero-art" aria-label="تصویر غذای منتخب منو">
                @if(!empty($media['hero']['url']))
                    <img class="kg-hero-photo" src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?: $settings->name }}" fetchpriority="high">
                @elseif($featuredProducts->first()?->image_url)
                    <img class="kg-hero-photo" src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" fetchpriority="high">
                @else
                    <div class="kg-hero-no-image" role="img" aria-label="تصویر غذا پس از ثبت محصول نمایش داده می‌شود">
                        <span>🍒</span><b>تصویر غذای منتخب</b><small>از بخش منو و محصولات بارگذاری کن</small>
                    </div>
                @endif
                <span class="kg-hero-spark kg-hero-spark-one"></span>
                <span class="kg-hero-spark kg-hero-spark-two"></span>
                <div class="kg-hero-art-label">
                    <span>✦</span><b>{{ $homepageContent['hero_badge_title'] }}</b>
                    <small>{{ $homepageContent['hero_badge_description'] }}</small>
                </div>
            </div>

            <div class="kg-hero-copy">
                <span class="kg-eyebrow"><i></i>{{ $homepageContent['hero_eyebrow'] }}</span>
                <h1 id="kg-home-title">{{ $homepageContent['hero_title_line1'] }}<br><em>{{ $homepageContent['hero_title_line2'] }}</em></h1>
                <p>{{ $homepageContent['hero_description'] }}</p>
                <div class="kg-hero-actions">
                    <a class="kg-button kg-button-primary" href="{{ route('public.menu') }}">{{ $homepageContent['hero_primary_cta'] }} <span>↗</span></a>
                    <a class="kg-button kg-button-light" href="{{ route('public.reservations.create') }}">{{ $homepageContent['hero_secondary_cta'] }} <span>↗</span></a>
                </div>
                @if($settings->phone)
                    <a class="kg-hero-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">
                        <span class="kg-phone-icon">☎</span>
                        <span><small>برای هماهنگی با مجموعه</small><b dir="ltr">{{ $settings->phone }}</b></span>
                    </a>
                @endif
            </div>

            <div class="kg-hero-bottom">
                <span><b>01</b><i></i> انتخاب از منوی واقعی Q Food</span>
                <a href="#home-showcase">برای کشف طعم‌ها پایین برو <b>↓</b></a>
            </div>
            <span class="kg-hero-decoration kg-hero-decoration-one" aria-hidden="true">✳</span>
            <span class="kg-hero-decoration kg-hero-decoration-two" aria-hidden="true">✦</span>
        </section>

        <section class="kg-home-showcase {{ !empty($media['home_showcase_background']['url']) ? 'has-custom-background' : '' }}" id="home-showcase" data-home-carousel aria-labelledby="kg-showcase-title" @if(!empty($media['home_showcase_background']['url'])) style="--kg-showcase-image: url('{{ $media['home_showcase_background']['url'] }}')" @endif>
            <div class="kg-home-showcase-inner">
                <header class="kg-home-showcase-heading">
                    <span class="kg-home-showcase-eyebrow">{{ $homepageContent['showcase_eyebrow'] }}</span>
                    <h2 id="kg-showcase-title">{{ $homepageContent['showcase_title'] }}</h2>
                    <span class="kg-home-showcase-rule"></span>
                    <p>{{ $homepageContent['showcase_description'] }}</p>
                </header>

                @if($featuredProducts->isNotEmpty())
                    <div class="kg-home-carousel" data-home-carousel-stage tabindex="0" aria-label="اسلایدر محصولات منتخب">
                        @foreach($featuredProducts as $product)
                            <a class="kg-home-carousel-card"
                               href="{{ route('public.menu') }}#category-{{ $product->category_id }}"
                               data-home-carousel-card
                               data-home-index="{{ $loop->index }}"
                               data-home-product="{{ $product->id }}"
                               aria-label="مشاهده {{ $product->name }}"
                               @if(!$loop->first) tabindex="-1" @endif>
                                <span class="kg-home-carousel-image">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" @if($loop->first) fetchpriority="high" @endif>
                                    @else
                                        <span class="kg-home-carousel-placeholder">🍒</span>
                                    @endif
                                </span>
                                <span class="kg-home-carousel-product">
                                    <b>{{ $product->name }}</b>
                                    <strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                                </span>
                                <span class="kg-home-carousel-open">مشاهده در منو ↗</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="kg-home-carousel-controls">
                        <button type="button" data-home-prev aria-label="محصول قبلی">→</button>
                        <div class="kg-home-carousel-dots" aria-label="انتخاب محصول">
                            @foreach($featuredProducts as $product)
                                <button type="button" data-home-dot="{{ $loop->index }}" class="{{ $loop->first ? 'is-active' : '' }}" aria-label="نمایش {{ $product->name }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>
                            @endforeach
                        </div>
                        <button type="button" data-home-next aria-label="محصول بعدی">←</button>
                    </div>
                @else
                    <div class="kg-home-carousel-empty">
                        <span>🍒</span><b>هنوز محصول فعالی برای نمایش وجود ندارد.</b>
                        <p>محصول‌ها و تصویرهای واقعی را از داشبورد اضافه کن؛ این بخش به‌صورت خودکار به‌روزرسانی می‌شود.</p>
                    </div>
                @endif
                <a class="kg-home-showcase-all" href="{{ route('public.menu') }}">رفتن به منوی کامل <span>↗</span></a>
            </div>
        </section>

        <section class="kg-section kg-categories" id="categories">
            <div class="kg-section-heading">
                <div><span class="kg-eyebrow">گشت‌وگذار در منو</span><h2>هوسِ کدام <em>طعم</em> را کرده‌ای؟</h2></div>
                <p>از دسته‌بندی موردعلاقه‌ات شروع کن و انتخاب‌های موجود را ببین.</p>
            </div>
            <div class="kg-category-grid">
                @forelse($categories as $category)
                    @php($categoryProduct = $category->products->first())
                    <a class="kg-category-card" href="{{ route('public.menu') }}#category-{{ $category->id }}">
                        <span class="kg-category-image">
                            @if($categoryProduct && $categoryProduct->image_url)
                                <img src="{{ $categoryProduct->image_url }}" alt="{{ $category->name }}" loading="lazy">
                            @else
                                <span class="kg-category-placeholder">🍒</span>
                            @endif
                            <i>{{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</i><b>↗</b>
                        </span>
                        <span class="kg-category-name">{{ $category->name }}</span>
                        <small>{{ number_format($category->available_products_count) }} محصول در منو</small>
                    </a>
                @empty
                    <div class="kg-empty-state"><span>🍒</span><b>منو در حال تکمیل است.</b><p>با ثبت دسته‌بندی و محصولات فعال در پنل، انتخاب‌ها اینجا نمایش داده می‌شوند.</p></div>
                @endforelse
            </div>
        </section>

        <section class="kg-story" id="about">
            <div class="kg-story-visual">
                @if(!empty($media['story_main']['url']))
                    <img src="{{ $media['story_main']['url'] }}" alt="{{ $media['story_main']['alt_text'] ?: 'فضای Q Food' }}" loading="lazy">
                @elseif($featuredProducts->first()?->image_url)
                    <img src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" loading="lazy">
                @else
                    <div class="kg-story-fallback"><span>🍒</span><b>لحظه‌های خوب<br>از انتخاب‌های ساده شروع می‌شوند.</b></div>
                @endif
                <div class="kg-story-sticker">با عشق<br>برای حال خوب</div>
            </div>
            <div class="kg-story-copy">
                <span class="kg-eyebrow">Q Food، برای لحظه‌های تو</span>
                <h2>یک فنجان،<br>یک میز،<br><em>یک حال خوب.</em></h2>
                <p>برای یک قرار کوتاه یا یک دورهمی طولانی، جزئیات مهم‌اند. منو را با اطلاعات ثبت‌شده ببین، سفارش بیرون‌بر را آنلاین بفرست یا برای زمان دلخواهت درخواست رزرو ثبت کن.</p>
                <div class="kg-story-links">
                    <a href="{{ route('public.menu') }}">منوی کافه <span>↗</span></a>
                    <a href="{{ route('public.reservations.create') }}">رزرو و مناسبت‌ها <span>↗</span></a>
                </div>
            </div>
        </section>


        @if(collect(['gallery_1', 'gallery_2', 'gallery_3'])->contains(fn ($slot) => !empty($media[$slot]['url'])))
            <section class="kg-photo-gallery" aria-labelledby="kg-photo-gallery-title">
                <header class="kg-photo-gallery-heading">
                    <span class="kg-eyebrow">نگاهی به Q Food</span>
                    <h2 id="kg-photo-gallery-title">فضای ما، <em>از نزدیک</em></h2>
                    <p>تصویرهای مجموعه از بخش رسانه داشبورد مدیریت می‌شوند.</p>
                </header>
                <div class="kg-photo-gallery-grid">
                    @foreach(['gallery_1' => 'فضای کافه', 'gallery_2' => 'غذا و نوشیدنی', 'gallery_3' => 'مناسبت‌ها و دورهمی'] as $slot => $label)
                        @if(!empty($media[$slot]['url']))
                            <figure class="kg-photo-gallery-card">
                                <img src="{{ $media[$slot]['url'] }}" alt="{{ $media[$slot]['alt_text'] ?: $label }}" loading="lazy">
                                <figcaption>{{ $label }}</figcaption>
                            </figure>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <section class="kg-reserve-banner">
            @if(!empty($media['home_reservation_banner']['url']))
                <img src="{{ $media['home_reservation_banner']['url'] }}" alt="{{ $media['home_reservation_banner']['alt_text'] ?: 'بنر رزرو Q Food' }}" loading="lazy">
            @elseif(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?: 'چیدمان میز' }}" loading="lazy">
            @elseif($featuredProducts->last()?->image_url)
                <img src="{{ $featuredProducts->last()->image_url }}" alt="{{ $featuredProducts->last()->name }}" loading="lazy">
            @endif
            <div class="kg-reserve-banner-overlay"></div>
            <div class="kg-reserve-banner-content">
                <span class="kg-eyebrow">تولد · دورهمی · قرار دوستانه</span>
                <h2>برای خاطره‌ای که<br><em>می‌ماند،</em> میز بچین.</h2>
                <p>زمان، تعداد مهمان و توضیحاتت را بفرست. درخواستت ثبت می‌شود و پس از بررسی ظرفیت، همکاران مجموعه برای تأیید نهایی هماهنگ می‌کنند.</p>
                <a class="kg-button kg-button-light" href="{{ route('public.reservations.create') }}">ثبت درخواست رزرو <span>↗</span></a>
            </div>
            <span class="kg-reserve-banner-cherry" aria-hidden="true">🍒</span>
        </section>
    </main>
    @include('public.partials.footer')
</body>
</html>
