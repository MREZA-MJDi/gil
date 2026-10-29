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
        <section class="gil-hero gil-hero-cinematic" id="home">
            <img class="gil-hero-photo" src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=2400&q=90" alt="چیدمان غذاهای تازه برای یک وعده کامل" fetchpriority="high">
            <div class="gil-hero-shade"></div>
            <div class="gil-hero-center">
                <span class="gil-eyebrow"><i></i> KHANE GILASI · CAFÉ & RESTAURANT</span>
                <p class="gil-welcome-script">طعم خوب، لحظهٔ خوب</p>
                <h1>{{ $settings->name }}</h1>
                <div class="gil-hero-signature"><span>COFFEE</span><i></i><span>KITCHEN</span><i></i><span>GOOD MOMENTS</span></div>
                <p class="gil-hero-lead">از اولین عطر قهوه تا غذایی که تازه از آشپزخانه می‌آید؛ برای انتخاب، سفارش و قرار بعدی‌ات همه‌چیز را روشن و راحت کرده‌ایم.</p>
                <div class="gil-hero-actions gil-hero-actions-centered">
                    <a class="gil-button gil-button-gold" href="{{ route('public.menu') }}">منو و سفارش <span>↗</span></a>
                    <a class="gil-button gil-button-glass" href="{{ route('public.reservations.create') }}">رزرو میز یا جشن <span>↗</span></a>
                </div>
                @if ($featuredProducts->isNotEmpty())
                    @php($heroProduct = $featuredProducts->first())
                    <a class="gil-hero-special" href="{{ route('public.menu') }}">
                        <span class="gil-hero-special-mark">✳</span>
                        <span><small>از منوی امروز</small><b>{{ $heroProduct->name }}</b></span>
                        <strong>{{ number_format($heroProduct->base_price) }} <small>{{ $settings->currency_label }}</small></strong>
                        <span class="gil-hero-special-arrow">↗</span>
                    </a>
                @endif
            </div>
            <div class="gil-hero-bottom gil-hero-bottom-cinematic">
                @if ($settings->phone)
                    <a class="gil-hero-phone" href="tel:{{ preg_replace('/[^\d+]/', '', $settings->phone) }}"><span>☎</span><b>{{ $settings->phone }}</b></a>
                @else
                    <span class="gil-hero-phone"><span>✳</span><b>میزت را انتخاب کن؛ لحظه‌ات را بساز.</b></span>
                @endif
                <a class="gil-hero-scroll" href="#menu"><span>برای کشف منو پایین برو</span><b>↓</b></a>
                <span class="gil-hero-edition">01 — THE GILASI EXPERIENCE</span>
            </div>
        </section>

        <section class="gil-category-strip section-wrap" aria-label="دسته‌بندی منو">
            <div class="gil-strip-intro">
                <span class="gil-eyebrow">EXPLORE THE MENU</span>
                <h2>هوسِ کدام طعم را کرده‌ای؟</h2>
                <p>از یک اسپرسوی کوتاه تا یک وعدهٔ کامل؛ از دسته‌بندی شروع کن.</p>
            </div>
            <div class="gil-category-links">
                @forelse ($categories as $category)
                    @php($categoryProduct = $category->products->first())
                    <a href="{{ route('public.menu') }}#category-{{ $category->id }}">
                        <span class="gil-category-image">
                            @if ($categoryProduct && $categoryProduct->image_url)
                                <img src="{{ $categoryProduct->image_url }}" alt="" loading="lazy">
                            @else
                                <img src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=700&q=82" alt="" loading="lazy">
                            @endif
                            <span class="gil-category-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="gil-category-arrow">↗</span>
                        </span>
                        <span class="gil-category-copy"><b>{{ $category->name }}</b><small>{{ number_format($category->products->count()) }} انتخاب از منو</small></span>
                    </a>
                @empty
                    <a href="{{ route('public.menu') }}"><span class="gil-category-image"><img src="https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=700&q=82" alt="" loading="lazy"></span><span class="gil-category-copy"><b>منوی کافه</b><small>مشاهدهٔ همهٔ انتخاب‌ها</small></span><span class="gil-category-arrow">↗</span></a>
                @endforelse
            </div>
        </section>

        <section class="gil-menu-feature section-wrap" id="menu">
            <div class="gil-section-heading">
                <div><span class="gil-eyebrow">MADE FOR YOUR MOOD</span><h2>انتخاب‌های<br><em>خوش‌طعم</em> ما.</h2></div>
                <div class="gil-section-heading-side"><p>این بخش از محصولات فعال پایگاه داده ساخته می‌شود؛ قیمت و گزینه‌ها همان اطلاعاتی‌اند که در پنل ثبت شده‌اند.</p><div class="gil-menu-side-actions"><a class="gil-text-link" href="{{ route('public.menu') }}">دیدن منوی کامل <span>↗</span></a><div class="gil-carousel-controls" aria-label="حرکت بین محصولات"><button type="button" data-food-carousel="prev" aria-label="محصولات قبلی">‹</button><button type="button" data-food-carousel="next" aria-label="محصولات بعدی">›</button></div></div></div>
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
