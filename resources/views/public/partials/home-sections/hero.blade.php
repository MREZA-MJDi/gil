<section class="gil-hero gil-hero-cinematic" id="home">
    <img class="gil-hero-photo" src="{{ $media['hero']['url'] ?? 'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=2400&q=90' }}" alt="چیدمان غذاهای تازه برای یک وعده کامل" fetchpriority="high">
    <div class="gil-hero-shade"></div>
    <div class="gil-hero-center">
        <span class="gil-eyebrow"><i></i> خانه گیلاسی · کافه و رستوران</span>
        <p class="gil-welcome-script">طعم خوب، لحظهٔ خوب</p>
        <h1>{{ $settings->name }}</h1>
        <div class="gil-hero-signature"><span>قهوه</span><i></i><span>آشپزخانه</span><i></i><span>لحظه‌های خوب</span></div>
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
        <span class="gil-hero-edition">۰۱ — تجربه خانه گیلاسی</span>
    </div>
</section>
