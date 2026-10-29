<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f4efe6">
    <meta name="description" content="{{ $settings->name }}؛ قهوه تخصصی، خوراکی‌های خوش‌حال‌کننده و مکث‌هایی برای حال بهتر.">
    <title>{{ $settings->name }} — قهوه، آشپزخانه، لحظه‌ها</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site">
    <div class="site-topline"><span>قهوه تازه · مواد اولیه باکیفیت · حال خوب</span><span class="site-topline-right"><i></i> امروز هم برای یک مکث خوشمزه بازیم</span></div>
    @include('public.partials.nav')
    <main>
        <section class="hero-section cup-stop" id="home" data-cup-stop>
            <div class="hero-copy">
                <span class="eyebrow"><span class="eyebrow-dot"></span> A LITTLE PAUSE, A LOT OF GOOD</span>
                <h1>روزت را<br><span>جرعه‌جرعه</span><br>بهتر کن.</h1>
                <p class="hero-description">قهوه‌ای که با حوصله دم می‌کشد، خوراکی‌ای که با عشق آماده می‌شود؛ و جایی که می‌توانی چند دقیقه از شلوغی دنیا فاصله بگیری.</p>
                <div class="hero-actions">
                    <a class="button button-dark" href="{{ route('public.menu') }}">کشف منو <span>↗</span></a>
                    <a class="button button-quiet" href="#story"><span class="button-play">↓</span> قصهٔ ما</a>
                    <a class="button button-quiet" href="#reservation">درخواست رزرو <span>↗</span></a>
                </div>
                <div class="hero-social-proof"><div class="mini-avatar-stack"><span>گ</span><span>ق</span><span>☕</span></div><div><b>برای لحظه‌های معمولیِ خاص</b><small>قهوه، غذا، آدم‌های خوب</small></div></div>
                <div class="hero-scribble" aria-hidden="true">slow down<br>sip something good <span>✳</span></div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-frame">
                    <img class="hero-image" src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1300&q=85" alt="قهوه تازه و ابزار دم‌آوری در کافه" fetchpriority="high">
                    <div class="hero-image-shade"></div>
                    <div class="hero-image-caption"><span>01 / 04</span><span>GOOD THINGS TAKE TIME</span></div>
                </div>
                <div class="hero-sticker" aria-label="قهوه تازه، هر روز"><span>FRESH</span><b>☕</b><span>EVERY DAY</span></div>
                <div class="floating-note note-top"><span class="note-line"></span><span>از دانه تا فنجان<br><b>همه‌چیز با دقت</b></span></div>
                <div class="floating-note note-bottom"><span class="note-icon">✳</span><span><b>لحظهٔ خودت</b><br>همین‌جا شروع می‌شود.</span></div>
            </div>
            <div class="hero-index"><span>01</span><span class="hero-index-line"></span><span>05</span></div>
        </section>

        <section class="ticker-section" aria-label="چیزهای خوب کافه"><div class="ticker-track"><span>قهوهٔ تازه</span><i>✳</i><span>آشپزخانهٔ گرم</span><i>✳</i><span>موسیقی ملایم</span><i>✳</i><span>آدم‌های دوست‌داشتنی</span><i>✳</i><span>قهوهٔ تازه</span><i>✳</i><span>آشپزخانهٔ گرم</span><i>✳</i><span>موسیقی ملایم</span><i>✳</i><span>آدم‌های دوست‌داشتنی</span><i>✳</i></div></section>

        <section class="section-wrap menu-section cup-stop" id="menu" data-cup-stop>
            <div class="section-heading">
                <div><span class="section-kicker">THE GOOD STUFF</span><h2>چیزهای خوشمزه<br><span>در انتظار تو.</span></h2></div>
                <div class="section-heading-aside"><p>از اولین جرعهٔ صبح تا آخرین لقمهٔ عصر؛ چیزی پیدا می‌شود که حال و هوایت را عوض کند.</p><a class="text-link" href="{{ route('public.menu') }}">دیدن همهٔ منو <span>↗</span></a></div>
            </div>
            <div class="featured-grid">
                @forelse ($featuredProducts as $index => $product)
                    <article class="food-card reveal-card">
                        <a href="{{ route('public.menu') }}" class="food-card-image">
                            @if ($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <div class="food-image-placeholder food-placeholder-{{ $index % 4 }}"><span>{{ ['☕','🥐','🍰','🥪'][$index % 4] }}</span><small>{{ $settings->name }}</small></div>
                            @endif
                            <span class="food-card-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="food-card-arrow">↗</span>
                        </a>
                        <div class="food-card-info"><div><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'آماده‌شده با دقت و مواد اولیهٔ باکیفیت.' }}</p></div><strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong></div>
                        @if ($product->options->isNotEmpty())
                            <div class="food-card-options">@foreach ($product->options->take(2) as $option)<span>{{ $option->name }} @if($option->price > 0) · +{{ number_format($option->price) }}@endif</span>@endforeach</div>
                        @endif
                    </article>
                @empty
                    <div class="catalog-empty"><span>✳</span><h3>منو در حال آماده‌شدن است.</h3><p>به‌زودی چیزهای خوشمزهٔ کافه در اینجا معرفی می‌شوند.</p><a class="text-link" href="{{ route('public.menu') }}">بازکردن صفحهٔ منو <span>↗</span></a></div>
                @endforelse
            </div>
            <div class="menu-bottom-note"><span>✳</span><p>سلیقه‌ها متفاوت‌اند؛ برای انتخاب دقیق، جزئیات مواد اولیه و افزودنی‌ها را در منو ببین.</p><a class="button button-outline" href="{{ route('public.menu') }}">منوی کامل <span>↗</span></a></div>
        </section>

        <section class="story-section cup-stop" id="story" data-cup-stop>
            <div class="story-left"><div class="story-image-main"><img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1100&q=85" alt="دست‌ها در حال آماده‌کردن قهوه" loading="lazy"></div><div class="story-image-small"><img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=650&q=85" alt="فضای گرم یک کافه" loading="lazy"></div><span class="story-roundel">MADE<br>WITH<br>CARE <b>✳</b></span></div>
            <div class="story-copy"><span class="section-kicker">A PLACE TO JUST BE</span><h2>قرار نیست<br>همه‌چیز <em>با عجله</em><br>اتفاق بیفتد.</h2><p>ما به چیزهای ساده‌ای باور داریم: قهوه‌ای که وقت کافی برای دم‌کشیدن داشته باشد، غذایی که تازه آماده شود، و فضایی که در آن مجبور نباشی نقش خاصی بازی کنی.</p><p>اینجا می‌توانی کار کنی، با دوستت گپ بزنی یا فقط به فنجانت نگاه کنی و نفسی تازه کنی. همین هم کافی‌ست.</p><a href="{{ route('public.menu') }}" class="text-link">با منوی ما آشنا شو <span>↗</span></a><div class="story-signature">take a breath. take a sip. <span>♡</span></div></div>
        </section>

        <section class="values-section cup-stop" id="ritual" data-cup-stop>
            <div class="values-heading"><span class="section-kicker">THE GILASI RITUAL</span><h2>خوبی در <span>جزئیات</span> است.</h2><p>سه دلیل کوچک برای اینکه بخشی از روزت را به ما بسپاری.</p></div>
            <div class="values-grid">
                <article class="value-card"><span class="value-number">01</span><div class="value-icon"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M13 24h31l-3 27H18z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M44 29h7q9 1 5 11-2 6-13 5M22 18c-5-5 5-7 0-12m11 12c-5-5 5-7 0-12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></div><h3>قهوه با حوصله</h3><p>از انتخاب دانه تا لحظهٔ سرو، توجه به جزئیات بخشی از تجربه است.</p><span class="value-doodle">☼</span></article>
                <article class="value-card value-card-featured"><span class="value-number">02</span><div class="value-icon"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M14 25h36v27H14z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M10 25h44l-4-11H14z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M25 52V38h14v14M18 19h28" fill="none" stroke="currentColor" stroke-width="2.5"/></svg></div><h3>خوراکی تازه</h3><p>برای هر محصول، توضیحات و گزینه‌های قابل انتخاب را شفاف در منو می‌بینی.</p><span class="value-doodle">✳</span></article>
                <article class="value-card"><span class="value-number">03</span><div class="value-icon"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 52S8 39 8 22q0-12 12-12 8 0 12 9 4-9 12-9 12 0 12 12Q56 39 32 52Z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M20 31q12 9 24 0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></div><h3>جای خودت</h3><p>برای قرار با دوست‌ها، یک ساعت کار یا چند دقیقه سکوت.</p><span class="value-doodle">♡</span></article>
            </div>
        </section>

        <section class="moments-section cup-stop" id="moments" data-cup-stop>
            <div class="moments-top"><div><span class="section-kicker">LITTLE MOMENTS, BIG FEELINGS</span><h2>یک گوشهٔ دنج<br>برای <em>لحظه‌های تو.</em></h2></div><p>صبح آرام، عصر شلوغ، یک قرار غیرمنتظره. هر بار می‌تواند داستان خودش را داشته باشد.</p></div>
            <div class="moments-collage">
                <figure class="moment-photo moment-photo-large"><img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1200&q=85" alt="فضای داخلی یک کافه دنج" loading="lazy"><figcaption>your little corner <span>↗</span></figcaption></figure>
                <figure class="moment-photo moment-photo-tall"><img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=85" alt="فنجان قهوه روی میز" loading="lazy"><figcaption>first sip of the day <span>↗</span></figcaption></figure>
                <figure class="moment-photo moment-photo-small"><img src="https://images.unsplash.com/photo-1559305616-3f99cd43e353?auto=format&fit=crop&w=900&q=85" alt="بار قهوه و تجهیزات کافه" loading="lazy"><figcaption>made with care <span>↗</span></figcaption></figure>
                <div class="moments-caption"><span>✳</span><p>همه‌چیز از یک<br>مکث کوچک شروع می‌شود.</p><a href="#visit" class="text-link">بیا ببینمت <span>↗</span></a></div>
            </div>
        </section>

        <section class="reservation-section cup-stop" id="reservation" data-cup-stop>
            <div class="reservation-section-heading">
                <span class="section-kicker">PLAN A LITTLE PAUSE</span>
                <h2>جای تو <em>محفوظ؟</em></h2>
                <p>درخواست رزروت را بفرست؛ بعد از بررسی تیم ما، وضعیت رزرو از طریق تماس با شماره‌ای که ثبت می‌کنی پیگیری می‌شود.</p>
            </div>
            <div id="public-reservation-app" data-endpoint="{{ route('public.reservations.store') }}"></div>
            <noscript><p class="reservation-noscript">برای ثبت درخواست رزرو، جاوااسکریپت مرورگرت را فعال کن یا با کافه تماس بگیر.</p></noscript>
        </section>

        <section class="visit-section cup-stop" id="visit" data-cup-stop>
            <div class="visit-sunburst" aria-hidden="true">✳</div>
            <div class="visit-copy"><span class="section-kicker">YOUR TABLE IS WAITING</span><h2>یک فنجان<br>با هم؟</h2><p>برای یک قرار کوتاه، یک شروع تازه یا استراحت بین کارها؛ در را باز می‌گذاریم.</p><div class="visit-actions"><a class="button button-cream" href="{{ route('public.menu') }}">اول منو را ببین <span>↗</span></a>@if ($settings->phone)<a class="button button-ghost-light" href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">تماس بگیر</a>@endif</div></div>
            <div class="visit-details">
                <div class="visit-detail"><span>آدرس</span><b>{{ $settings->address ?: 'آدرس کافه را از بخش تنظیمات تکمیل کنید.' }}</b></div>
                <div class="visit-detail"><span>تلفن</span><b>{{ $settings->phone ?: 'شماره تماس ثبت نشده است.' }}</b></div>
                <div class="visit-detail"><span>تجربهٔ آنلاین</span><b>منو و قیمت‌ها را هر زمان ببین.</b></div>
                <a class="visit-map-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($settings->address ?: $settings->name) }}" target="_blank" rel="noopener noreferrer">بازکردن مسیر در نقشه <span>↗</span></a>
            </div>
        </section>
    </main>
    @include('public.partials.footer')
    @include('public.partials.traveling-cup')
</body>
</html>