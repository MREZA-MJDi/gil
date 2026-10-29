<header class="site-header" data-site-header>
    <a class="site-brand" href="{{ route('home') }}" aria-label="صفحه اصلی {{ $settings->name }}">
        <span class="site-brand-mark"><svg viewBox="0 0 36 36" aria-hidden="true"><path d="M9 12h18l-2 16H11z" fill="currentColor"/><path d="M8 10h20v4H8z" fill="#d45b3d"/><path d="M15 7c-4-4 3-4 0-7M21 7c-4-4 3-4 0-7" transform="translate(0 3)" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
        <span class="site-brand-copy"><b>{{ $settings->name }}</b><small>COFFEE · KITCHEN · MOMENTS</small></span>
    </a>
    <button class="site-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-label="باز کردن منو"><span></span><span></span></button>
    <nav class="site-nav" data-site-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}#home">خانه</a>
        <a href="{{ route('public.menu') }}">منو و خوراکی‌ها</a>
        <a href="{{ route('home') }}#story">داستان ما</a>
        <a href="{{ route('home') }}#moments">فضای کافه</a>
        <a href="{{ route('home') }}#visit">تماس و مسیر</a>
    </nav>
    <a class="site-header-cta" href="{{ route('public.menu') }}">منو را ببین <span>↗</span></a>
</header>