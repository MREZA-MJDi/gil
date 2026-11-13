<header class="kg-header" data-kg-header>
    <div class="kg-header-inner">
        <a class="kg-brand" href="{{ route('home') }}" aria-label="صفحه اصلی {{ $settings->name }}">
            <span class="kg-brand-mark" aria-hidden="true">
                @if(!empty($media['brand_logo']['url']))
                    <img class="kg-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="">
                @else
                <svg viewBox="0 0 48 48" role="img">
                    <path d="M23 19C22 8 15 6 11 5" fill="none" stroke="#55784d" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M24 18C27 7 34 6 39 8" fill="none" stroke="#55784d" stroke-width="2.8" stroke-linecap="round"/>
                    <path d="M11 5C17 2 22 5 24 12C17 13 13 11 11 5Z" fill="#718e5d"/>
                    <path d="M39 8C33 3 27 7 24 14C32 15 37 13 39 8Z" fill="#718e5d"/>
                    <path d="M23 20C13 17 7 25 10 34C13 44 25 43 25 31C25 25 24 22 23 20Z" fill="#a61935"/>
                    <path d="M25 20C35 16 42 23 39 33C36 43 25 43 24 32C23 26 24 22 25 20Z" fill="#c32640"/>
                    <path d="M16 27C16 24 18 23 20 24" fill="none" stroke="#f9d5d2" stroke-width="2" stroke-linecap="round"/>
                    <path d="M31 26C31 23 34 22 35 24" fill="none" stroke="#f9d5d2" stroke-width="2" stroke-linecap="round"/>
                </svg>
                @endif
            </span>
            <span class="kg-brand-copy">
                <strong>{{ $settings->name ?: 'خانه گیلاسی' }}</strong>
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
                    <a class="kg-nav-admin" href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                    <form class="kg-nav-logout" method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit">خروج</button>
                    </form>
                @else
                    <a class="kg-nav-admin" href="{{ route('customer.dashboard') }}">حساب من</a>
                @endif
            @endauth
            @if(!auth()->check() || !auth()->user()->is_admin)
                <a class="kg-nav-admin" href="{{ route('admin.login') }}">ورود مدیر</a>
            @endif
        </nav>

        <a class="kg-header-cta" href="{{ route('public.menu') }}">دیدن منو </a>
    </div>
</header>
