<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171614">
    <meta name="description" content="{{ $settings->name }}؛ منوی واقعی کافه، سفارش مستقیم، QR میز و هماهنگی رزرو و مراسم.">
    <title>{{ $settings->name }} | قهوه، غذا، تجربه</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-home-v2">
    <div class="site-topline">
        <span>قهوهٔ دقیق · غذای تازه · میزبانی باحوصله</span>
        <span class="site-topline-right"><i></i> هر انتخاب، با جزئیات روشن</span>
    </div>
    @include('public.partials.nav')
    <main class="gil-home">
        <section class="gil-hero" id="home">
            <img class="gil-hero-photo" src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=2200&q=90" alt="فضای رستوران با نور گرم و میزهای آماده سرو" fetchpriority="high">
            <div class="gil-hero-shade"></div>
            <div class="gil-hero-layout">
                <div class="gil-hero-copy">
                    <span class="gil-eyebrow"><i></i> GIL CAFE · KITCHEN · MOMENTS</span>
                    <h1>برای طعم‌های<br><em>ماندگار</em> وقت بگذار.</h1>
                    <p>قهوه‌ای که با دقت آماده می‌شود، غذایی که تازه از آشپزخانه می‌آید و تجربه‌ای که از انتخاب منو تا سفارش، شفاف و راحت پیش می‌رود.</p>
                    <div class="gil-hero-actions">
                        <a class="gil-button gil-button-gold" href="{{ route('public.menu') }}">کشف منو <span>↗</span></a>
                        <a class="gil-button gil-button-glass" href="{{ route('public.reservations.create') }}">رزرو میز یا جشن <span>↗</span></a>
                    </div>
                    <div class="gil-hero-proof">
                        <div><b>منوی زنده</b><span>قیمت و گزینه‌های ثبت‌شده</span></div>
                        <div><b>سفارش مستقیم</b><span>ثبت و پیگیری از سیستم</span></div>
                        <div><b>رزرو با هماهنگی</b><span>میز، تولد و مراسم</span></div>
                    </div>
                </div>
                <aside class="gil-hero-feature">
                    @if ($featuredProducts->isNotEmpty())
                        @php($heroProduct = $featuredProducts->first())
                        <div class="gil-hero-feature-image">
                            @if ($heroProduct->image_url)
                                <img src="{{ $heroProduct->image_url }}" alt="{{ $heroProduct->name }}">
                            @else
                                <img src="https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=900&q=85" alt="فنجان قهوه">
                            @endif
                            <span class="gil-feature-tag">پیشنهاد از منوی کافه</span>
                        </div>
                        <div class="gil-hero-feature-body">
                            <div><small>پیشنهاد امروز</small><h2>{{ $heroProduct->name }}</h2><p>{{ $heroProduct->description ?: 'جزئیات و گزینه‌های این محصول در منوی به‌روز نمایش داده می‌شود.' }}</p></div>
                            <strong>{{ number_format($heroProduct->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                        </div>
                        <a class="gil-feature-link" href="{{ route('public.menu') }}">مشاهدهٔ منو <span>↗</span></a>
                    @else
                        <div class="gil-hero-feature-image"><img src="https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=900&q=85" alt="فنجان قهوه تازه"></div>
                        <div class="gil-hero-feature-body"><div><small>منوی خانه گیلاسی</small><h2>طعم موردعلاقه‌ات را پیدا کن.</h2></div></div>
                        <a class="gil-feature-link" href="{{ route('public.menu') }}">مشاهدهٔ منو <span>↗</span></a>
                    @endif
                </aside>
            </div>
            <div class="gil-hero-bottom"><span>یک میز برای دیدار؛ یک فنجان برای مکث.</span><span>01 — GILASI EXPERIENCE</span></div>
        </section>

        <section class="gil-category-strip section-wrap" aria-label="دسته‌بندی منو">
            <div class="gil-strip-intro"><span class="gil-eyebrow">EXPLORE THE MENU</span><h2>از کجا شروع کنیم؟</h2></div>
            <div class="gil-category-links">
                @forelse ($categories as $category)
                    <a href="{{ route('public.menu') }}#category-{{ $category->id }}">
                        <span class="gil-category-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <b>{{ $category->name }}</b>
                        <span class="gil-category-arrow">↗</span>
                    </a>
                @empty
                    <a href="{{ route('public.menu') }}"><b>منو را ببین</b><span class="gil-category-arrow">↗</span></a>
                @endforelse
            </div>
        </section>

        <section class="gil-menu-feature section-wrap" id="menu">
            <div class="gil-section-heading">
                <div><span class="gil-eyebrow">MADE FOR YOUR MOOD</span><h2>انتخاب‌های<br><em>خوش‌طعم</em> ما.</h2></div>
                <div class="gil-section-heading-side"><p>منو از محصولات فعال مجموعه می‌آید؛ قیمت و افزودنی‌ها را باز کن، انتخاب کن و سفارشت را مستقیم ثبت کن.</p><a class="gil-text-link" href="{{ route('public.menu') }}">دیدن منوی کامل <span>↗</span></a></div>
            </div>
            <div class="gil-food-grid">
                @forelse ($featuredProducts as $index => $product)
                    <article class="gil-food-card">
                        <a href="{{ route('public.menu') }}" class="gil-food-photo">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <img src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=900&q=85" alt="غذای آماده سرو" loading="lazy">
                            @endif
                            <span class="gil-food-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="gil-food-open">مشاهده <b>↗</b></span>
                        </a>
                        <div class="gil-food-meta"><div><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'برای ترکیبات و گزینه‌های قابل انتخاب، منوی کامل را ببین.' }}</p></div><strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong></div>
                        @if ($product->options->isNotEmpty())
                            <div class="gil-food-options">@foreach ($product->options->take(2) as $option)<span>{{ $option->name }} @if ($option->price > 0) <i>+{{ number_format($option->price) }}</i>@endif</span>@endforeach</div>
                        @endif
                    </article>
                @empty
                    <div class="gil-empty-menu"><span>✳</span><h3>منو در حال آماده‌شدن است.</h3><p>با ثبت محصولات فعال در داشبورد، غذاها و نوشیدنی‌ها به‌صورت خودکار اینجا نمایش داده می‌شوند.</p><a class="gil-text-link" href="{{ route('admin.login') }}">ورود کارکنان برای تکمیل منو <span>↗</span></a></div>
                @endforelse
            </div>
            <div class="gil-menu-footer"><span>اطلاعات مواد اولیه و آلرژن‌ها بر اساس داده‌ای است که کافه ثبت کرده است.</span><a class="gil-button gil-button-dark" href="{{ route('public.menu') }}">منوی کامل و سفارش آنلاین <span>↗</span></a></div>
        </section>

        <section class="gil-experience">
            <div class="gil-experience-images">
                <div class="gil-experience-image gil-experience-image-main"><img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1200&q=88" alt="غذای تازه در فضای رستوران" loading="lazy"><span>از آشپزخانه تا میز تو</span></div>
                <div class="gil-experience-image gil-experience-image-small"><img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=85" alt="قهوه در حال آماده‌شدن" loading="lazy"><span>قهوه با دقت</span></div>
                <div class="gil-image-note"><b>THE DETAILS MATTER</b><span>ترکیب خوب، انتخاب روشن، سرو باحوصله.</span></div>
            </div>
            <div class="gil-experience-copy">
                <span class="gil-eyebrow">NOT JUST A STOP</span>
                <h2>اینجا برای<br>یک تجربهٔ <em>کامل</em><br>آمده‌ای.</h2>
                <p>برای یک قهوهٔ کوتاه، یک وعدهٔ کامل، قرار دوستانه یا برنامه‌ریزی یک مناسبت. منو و مسیر سفارش ساده است تا تمرکزت روی لحظه‌ای باشد که برایش آمده‌ای.</p>
                <div class="gil-experience-points">
                    <div><span>01</span><p><b>انتخاب شفاف</b><small>قیمت، ترکیبات ثبت‌شده و افزودنی‌ها در منو</small></p></div>
                    <div><span>02</span><p><b>سفارش راحت</b><small>از میز با QR یا مستقیم از صفحهٔ منو</small></p></div>
                    <div><span>03</span><p><b>برنامه‌ریزی مناسبت</b><small>درخواست رزرو میز، تولد و هماهنگی مراسم</small></p></div>
                </div>
                <a class="gil-text-link" href="{{ route('public.reservations.create') }}">برای قرار بعدی برنامه‌ریزی کن <span>↗</span></a>
            </div>
        </section>

        <section class="gil-ordering section-wrap" id="moments">
            <div class="gil-section-heading">
                <div><span class="gil-eyebrow">A BETTER WAY TO ORDER</span><h2>هرجا هستی،<br><em>ساده سفارش بده.</em></h2></div>
                <div class="gil-section-heading-side"><p>سه راه روشن برای تجربهٔ خانه گیلاسی؛ بدون اینکه بین چند مسیر نامشخص سردرگم شوی.</p></div>
            </div>
            <div class="gil-ordering-grid">
                <a href="{{ route('public.menu') }}" class="gil-order-card gil-order-card-dark"><span class="gil-order-icon">↗</span><span class="gil-order-number">01 / DIRECT</span><h3>سفارش از سایت</h3><p>منو را ببین، گزینه‌ها را انتخاب کن و سفارش بیرون‌بر را ثبت کن. مبلغ نهایی سمت سرور محاسبه می‌شود.</p><b>رفتن به منو <span>↗</span></b></a>
                <article class="gil-order-card gil-order-card-light"><span class="gil-order-icon">▦</span><span class="gil-order-number">02 / TABLE QR</span><h3>سفارش از میز</h3><p>QR همان میز را اسکن کن؛ سفارش به همان میز وصل می‌شود تا کارکنان بتوانند آن را پیگیری کنند.</p><span class="gil-order-card-note">درخواست پرداخت از این صفحه به معنی پرداخت موفق نیست.</span></article>
                <a href="{{ route('public.reservations.create') }}" class="gil-order-card gil-order-card-olive"><span class="gil-order-icon">◷</span><span class="gil-order-number">03 / BOOK A MOMENT</span><h3>رزرو و جشن‌ها</h3><p>برای میز، جشن تولد، مشاوره، دورهمی خصوصی یا کترینگ درخواست بفرست؛ تیم کافه جزئیات را تأیید می‌کند.</p><b>ثبت درخواست رزرو <span>↗</span></b></a>
            </div>
        </section>

        <section class="gil-event-banner" id="story">
            <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=2200&q=88" alt="میز چیده‌شده برای یک مناسبت" loading="lazy">
            <div class="gil-event-shade"></div>
            <div class="gil-event-content"><span class="gil-eyebrow">BIRTHDAYS · PRIVATE EVENTS · CATERING</span><h2>برای خاطره‌ای<br>که <em>می‌ماند</em>، میز بچین.</h2><p>تعداد مهمان، زمان، بودجهٔ تقریبی و جزئیات مراسم را بفرست. درخواستت در پنل ثبت می‌شود و هماهنگی نهایی با تیم کافه انجام خواهد شد.</p><a class="gil-button gil-button-gold" href="{{ route('public.reservations.create') }}">شروع برنامه‌ریزی <span>↗</span></a></div>
        </section>

        <section class="gil-gallery section-wrap">
            <div class="gil-gallery-heading"><div><span class="gil-eyebrow">A LOOK INSIDE</span><h2>جزئیاتی که<br><em>حال می‌سازند.</em></h2></div><p>از فنجان اول تا میز دورهمی؛ هر بخش از تجربه سهم خودش را دارد.</p></div>
            <div class="gil-gallery-grid">
                <figure class="gil-gallery-item gil-gallery-large"><img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1300&q=88" alt="فضای داخلی کافه و رستوران" loading="lazy"><figcaption><b>فضای ملاقات</b><span>برای مکث، گفت‌وگو و دورهمی</span></figcaption></figure>
                <figure class="gil-gallery-item"><img src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=900&q=88" alt="غذاهای آماده سرو" loading="lazy"><figcaption><b>از آشپزخانه</b><span>انتخاب‌های منو</span></figcaption></figure>
                <figure class="gil-gallery-item"><img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=900&q=88" alt="میزهای کافه" loading="lazy"><figcaption><b>یک میز برای تو</b><span>رزرو و برنامه‌ریزی</span></figcaption></figure>
            </div>
        </section>

        <section class="gil-final-cta section-wrap" id="visit">
            <div><span class="gil-eyebrow">YOUR NEXT GOOD MOMENT</span><h2>لحظهٔ بعدی را<br><em>خوش‌طعم انتخاب کن.</em></h2></div>
            <div><p>منوی به‌روز را ببین یا برای میز و مناسبت بعدی درخواست رزرو بفرست.</p><div class="gil-hero-actions"><a class="gil-button gil-button-gold" href="{{ route('public.menu') }}">دیدن منو <span>↗</span></a><a class="gil-button gil-button-outline" href="{{ route('public.reservations.create') }}">رزرو و جشن‌ها <span>↗</span></a></div></div>
        </section>
    </main>
    @include('public.partials.footer')
</body>
</html>
