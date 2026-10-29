<footer class="site-footer">
    <div class="site-footer-top">
        <a class="site-brand site-brand-footer" href="{{ route('home') }}">
            <span class="site-brand-mark"><svg viewBox="0 0 36 36" aria-hidden="true"><path d="M9 12h18l-2 16H11z" fill="currentColor"/><path d="M8 10h20v4H8z" fill="#d45b3d"/></svg></span>
            <span class="site-brand-copy"><b>{{ $settings->name }}</b><small>TAKE A LITTLE PAUSE.</small></span>
        </a>
        <p>یک فنجان خوب، یک مکث کوچک، یک روز بهتر.</p>
        <a class="site-footer-up" href="#home" aria-label="بازگشت به بالای صفحه">↑</a>
    </div>
    <div class="site-footer-bottom">
        <span>© {{ date('Y') }} {{ $settings->name }} · ساخته‌شده برای لحظه‌های خوب</span>
        <div><a href="{{ route('public.menu') }}">منوی کافه</a><a href="{{ route('home') }}#visit">مسیریابی</a><a href="{{ route('admin.login') }}">ورود کارکنان</a></div>
    </div>
</footer>