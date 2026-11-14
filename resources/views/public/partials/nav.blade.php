<header class="kg-header" data-kg-header>
    <div class="kg-header-inner">
        <a class="kg-brand" href="{{ route('home') }}" aria-label="صفحه اصلی Q Food">
            <span class="kg-brand-copy">
                <strong>Q Food</strong>
                <small>کافه و رستوران</small>
            </span>
        </a>

        <button class="kg-menu-toggle" type="button" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-main-nav" aria-label="باز کردن منو">
            <span></span><span></span>
        </button>

        <nav class="kg-nav" id="kg-main-nav" data-kg-nav aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>خانه</a>
            <a href="{{ route('public.menu') }}" @if(request()->routeIs('public.menu')) aria-current="page" @endif>منو و سفارش</a>
            <a href="{{ route('public.reservations.create') }}" @if(request()->routeIs('public.reservations.create')) aria-current="page" @endif>رزرو و مناسبت‌ها</a>
            <a href="{{ route('home') }}#about">درباره ما</a>
            @auth
                @if (auth()->user()->is_admin)
                    <a class="kg-nav-admin" href="{{ route('admin.dashboard') }}">پنل مدیریت <span>↗</span></a>
                    <form class="kg-nav-logout" method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit">خروج</button>
                    </form>
                @else
                    <a class="kg-nav-admin" href="{{ route('admin.login') }}">ورود کارکنان</a>
                @endif
            @else
                <a class="kg-nav-admin" href="{{ route('admin.login') }}">ورود کارکنان</a>
            @endauth
        </nav>

        <a class="kg-header-cta" href="{{ route('public.menu') }}">دیدن منو <span>↗</span></a>
    </div>
</header>
