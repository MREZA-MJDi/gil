<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ec">
    <meta name="description" content="{{ $settings->name }}؛ منو، سفارش بیرون‌بر و درخواست رزرو میز و مناسبت‌ها.">
    <title>{{ $settings->name }} | کافه و رستوران</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site kg-home" id="top">
    <div class="kg-topline">
        <span>🍒 یک مکث کوچک برای حال خوب</span>
        <span>طعم‌ها را ببین، لحظه‌ات را انتخاب کن</span>
    </div>
    @include('public.partials.nav')

    <main>
        <section class="kg-hero">
            <div class="kg-hero-copy">
                <span class="kg-eyebrow"><i></i> کافه و رستوران {{ $settings->name }}</span>
                <h1>طعم خوب،<br><em>حال خوب.</em></h1>
                <p>برای یک قهوه کوتاه، یک وعده خوش‌طعم یا یک دورهمی دوست‌داشتنی؛ انتخاب و سفارش را ساده کرده‌ایم تا وقتت برای لذت‌بردن بماند.</p>
                <div class="kg-hero-actions">
                    <a class="kg-button kg-button-primary" href="{{ route('public.menu') }}">دیدن منو و سفارش <span>↗</span></a>
                    <a class="kg-button kg-button-outline" href="{{ route('public.reservations.create') }}">درخواست رزرو <span>↗</span></a>
                </div>
                @if($settings->phone)
                    <a class="kg-hero-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">
                        <span class="kg-phone-icon">☎</span>
                        <span><small>برای هماهنگی با مجموعه</small><b dir="ltr">{{ $settings->phone }}</b></span>
                    </a>
                @endif
            </div>

            <div class="kg-hero-art" aria-label="تصویر فضای خانه گیلاسی">
                @if(!empty($media['hero']['url']))
                    <img class="kg-hero-photo" src="{{ $media['hero']['url'] }}" alt="{{ $media['hero']['alt_text'] ?? 'فضای خانه گیلاسی' }}" fetchpriority="high">
                @elseif($featuredProducts->first() && $featuredProducts->first()->image_url)
                    <img class="kg-hero-photo" src="{{ $featuredProducts->first()->image_url }}" alt="{{ $featuredProducts->first()->name }}" fetchpriority="high">
                @else
                    <div class="kg-hero-fallback">
                        <span class="kg-fallback-sun"></span>
                        <span class="kg-fallback-plate"></span>
                        <span class="kg-fallback-cup">☕</span>
                    </div>
                @endif
                <div class="kg-hero-art-label"><span>🍒</span><b>با عشق پخته‌شده</b><small>از آشپزخانه تا میز تو</small></div>
                <span class="kg-hero-deco kg-hero-deco-one">✳</span>
                <span class="kg-hero-deco kg-hero-deco-two">♡</span>
            </div>

            <div class="kg-hero-bottom">
                <span>01 <i></i> انتخابی از منوی خانه گیلاسی</span>
                <a href="#categories">برای کشف منو پایین برو <b>↓</b></a>
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
                                <img src="{{ $categoryProduct->image_url }}" alt="" loading="lazy">
                            @else
                                <span class="kg-category-placeholder">{{ ['☕','🥐','🍰','🍽️'][$loop->index % 4] }}</span>
                            @endif
                            <i>{{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</i>
                            <b>↗</b>
                        </span>
                        <span class="kg-category-name">{{ $category->name }}</span>
                        <small>{{ $category->products->count() }} محصول فعال</small>
                    </a>
                @empty
                    <div class="kg-empty-state">
                        <span>🍒</span><b>منو در حال تکمیل است.</b>
                        <p>با ثبت دسته‌بندی و محصولات فعال در پنل، انتخاب‌ها اینجا نمایش داده می‌شوند.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="kg-section kg-featured" id="featured">
            <div class="kg-section-heading">
                <div><span class="kg-eyebrow">پیشنهادهای منو</span><h2>چند انتخاب <em>دوست‌داشتنی</em></h2></div>
                <a class="kg-text-link" href="{{ route('public.menu') }}">مشاهده منوی کامل <span>↗</span></a>
            </div>
            <div class="kg-product-grid">
                @forelse($featuredProducts as $product)
                    <article class="kg-product-card">
                        <a class="kg-product-image" href="{{ route('public.menu') }}#category-{{ $product->category_id }}" aria-label="مشاهده {{ $product->name }}">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <span class="kg-product-placeholder">🍒</span>
                            @endif
                            <span>مشاهده در منو ↗</span>
                        </a>
                        <div class="kg-product-card-body">
                            <div><h3>{{ $product->name }}</h3><p>{{ \Illuminate\Support\Str::limit($product->description ?: 'برای دیدن جزئیات و افزودنی‌های این محصول منوی کامل را ببین.', 100) }}</p></div>
                            <strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                        </div>
                    </article>
                @empty
                    <div class="kg-empty-state kg-empty-wide">
                        <span>🥣</span><b>هنوز محصول فعالی ثبت نشده است.</b>
                        <p>پس از ثبت محصولات واقعی در پنل، این بخش به‌صورت خودکار به‌روزرسانی می‌شود.</p>
                    </div>
                @endforelse
            </div>
            <div class="kg-featured-foot">
                <span>قیمت‌ها و گزینه‌های انتخابی از اطلاعات ثبت‌شده در پنل خوانده می‌شوند.</span>
                <a class="kg-button kg-button-dark" href="{{ route('public.menu') }}">رفتن به منو <span>↗</span></a>
            </div>
        </section>

        <section class="kg-story" id="about">
            <div class="kg-story-visual">
                @if(!empty($media['story_main']['url']))
                    <img src="{{ $media['story_main']['url'] }}" alt="{{ $media['story_main']['alt_text'] ?? 'فضای خانه گیلاسی' }}" loading="lazy">
                @else
                    <div class="kg-story-fallback"><span>🍒</span><b>لحظه‌های خوب<br>از انتخاب‌های ساده شروع می‌شوند.</b></div>
                @endif
                <div class="kg-story-sticker">با عشق<br>برای حال خوب</div>
            </div>
            <div class="kg-story-copy">
                <span class="kg-eyebrow">خانه گیلاسی، برای لحظه‌های تو</span>
                <h2>یک فنجان،<br>یک میز،<br><em>یک حال خوب.</em></h2>
                <p>برای یک قرار کوتاه یا یک دورهمی طولانی، جزئیات مهم‌اند. منو را با اطلاعات ثبت‌شده ببین، سفارش بیرون‌بر را آنلاین بفرست یا برای زمان دلخواهت درخواست رزرو ثبت کن.</p>
                <div class="kg-story-links">
                    <a href="{{ route('public.menu') }}">منوی کافه <span>↗</span></a>
                    <a href="{{ route('public.reservations.create') }}">رزرو و مناسبت‌ها <span>↗</span></a>
                </div>
            </div>
        </section>

        <section class="kg-reserve-banner">
            @if(!empty($media['gallery_3']['url']))
                <img src="{{ $media['gallery_3']['url'] }}" alt="{{ $media['gallery_3']['alt_text'] ?? 'چیدمان میز' }}" loading="lazy">
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
