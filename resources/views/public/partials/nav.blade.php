@php($brandLogo = \App\Models\SiteMediaAsset::urlFor('brand_logo'))
<header class="site-header" data-site-header>
    <a class="site-brand" href="{{ route('home') }}" aria-label="صفحه اصلی {{ $settings->name }}">
        <span class="site-brand-mark">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="" />@else<svg viewBox="0 0 36 36" aria-hidden="true"><path d="M9 12h18l-2 16H11z" fill="currentColor"/><path d="M8 10h20v4H8z" fill="#f2a6ae"/><path d="M15 7c-4-4 3-4 0-7M21 7c-4-4 3-4 0-7" transform="translate(0 3)" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>@endif</span>
        @if (request()->routeIs('home'))
            <span class="site-brand-copy"><b><span>KHANE</span> <em>GILASI</em></b><small>CAFÉ &amp; RESTAURANT</small></span>
        @else
            <span class="site-brand-copy"><b>{{ $settings->name }}</b><small>قهوه · آشپزخانه · لحظه‌های خوب</small></span>
        @endif
    </a>
    <button class="site-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-label="باز کردن منو"><span></span><span></span></button>
    <nav class="site-nav" data-site-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}#home">@if (request()->routeIs('home')) Home @else خانه @endif</a>
        <a href="{{ route('public.menu') }}">@if (request()->routeIs('home')) Menu @else منو و خوراکی‌ها @endif</a>
        <a href="{{ route('home') }}#reservation">@if (request()->routeIs('home')) Reservations @else رزرو میز @endif</a>
        <a href="{{ route('home') }}#story">@if (request()->routeIs('home')) Our Story @else داستان ما @endif</a>
        <a href="{{ route('home') }}#visit">@if (request()->routeIs('home')) Contact @else تماس و مسیر @endif</a>
        @auth
            @if (auth()->user()->is_admin)
                <a class="site-nav-auth site-nav-dashboard" href="{{ route('admin.dashboard') }}">پنل مدیریت <span>↗</span></a>
                <form class="site-nav-logout" method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="site-nav-auth site-nav-logout-button" type="submit">خروج <span>↗</span></button>
                </form>
            @else
                <a class="site-nav-auth" href="{{ route('admin.login') }}">ورود کارکنان</a>
            @endif
        @else
            <a class="site-nav-auth site-nav-login" href="{{ route('admin.login') }}">ورود کارکنان <span>↗</span></a>
        @endauth
    </nav>
    <a class="site-header-cta" href="{{ route('public.menu') }}">@if (request()->routeIs('home')) سفارش آنلاین @else منو را ببین @endif <span>↗</span></a>
</header>