<header class="nav ref-nav" data-kg-header>
    <button type="button" class="burger" data-kg-menu-toggle aria-expanded="false" aria-label="باز کردن منو"><i></i><i></i></button>
    <a class="brand" href="{{ route('home') }}">
        @if(!empty($media['brand_logo']['url']))<img class="ref-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="{{ $media['brand_logo']['alt_text'] ?: $settings->name }}">@endif
        <span>{{ $settings->name }}</span>
    </a>
    <nav class="ref-nav-links" data-kg-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>خانه</a>
        <a href="{{ route('public.menu') }}" @if(request()->routeIs('public.menu')) aria-current="page" @endif>منو</a>
        <a href="{{ route('public.reservations.create') }}" @if(request()->routeIs('public.reservations.create')) aria-current="page" @endif class="cta">رزرو میز</a>
        @guest<a href="{{ route('customer.login') }}">ورود مشتری</a>
        @else
            @can('access-customer')<a href="{{ route('customer.dashboard') }}" @if(request()->routeIs('customer.dashboard')) aria-current="page" @endif>حساب من</a>@endcan
            @can('access-admin')<a href="{{ route('admin.dashboard') }}">مدیریت</a>@endcan
        @endguest
        @guest
            <a class="login-link" href="{{ route('admin.login') }}" aria-label="ورود مدیر">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"/></svg>
                ورود مدیر
            </a>
        @else
            @if(!auth()->user()->is_admin)
                <a class="login-link" href="{{ route('admin.login') }}" aria-label="ورود مدیر">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"/></svg>
                    ورود مدیر
                </a>
            @endif
        @endguest
    </nav>
</header>