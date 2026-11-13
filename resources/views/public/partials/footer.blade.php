<footer class="kg-footer">
    <div class="kg-footer-main">
        <div class="kg-footer-brand">
            <a class="kg-brand kg-brand-footer" href="{{ route('home') }}">
                <span class="kg-brand-mark" aria-hidden="true">
                    @if(!empty($media['brand_logo']['url']))
                        <img class="kg-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="">
                    @else
                        🍒
                    @endif
                </span>
                <span class="kg-brand-copy"><strong>{{ $settings->name }}</strong><small>کافه و رستوران</small></span>
            </a>
            <p>یک مکث خوش‌طعم، یک گفت‌وگوی خوب و لحظه‌ای که برای خودت نگه می‌داری.</p>
        </div>
        <div class="kg-footer-links">
            <strong>دسترسی سریع</strong>
            <a href="{{ route('home') }}">صفحه اصلی</a>
            <a href="{{ route('public.menu') }}">منو و سفارش</a>
            <a href="{{ route('public.reservations.create') }}">درخواست رزرو</a>
        </div>
        <div class="kg-footer-contact">
            <strong>راه‌های ارتباطی</strong>
            @if($settings->phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}" dir="ltr">{{ $settings->phone }}</a>
            @endif
            @if($settings->address)
                <p>{{ $settings->address }}</p>
            @endif
            @if(!$settings->phone && !$settings->address)
                <p>اطلاعات تماس از بخش تنظیمات مجموعه نمایش داده می‌شود.</p>
            @endif
        </div>
    </div>
    <div class="kg-footer-bottom">
        <span>© {{ now()->year }} {{ $settings->name }}. تمامی حقوق محفوظ است.</span>
        <a href="#top">بازگشت به بالا ↑</a>
    </div>
</footer>
