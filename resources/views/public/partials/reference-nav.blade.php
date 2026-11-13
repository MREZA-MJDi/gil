<header class="nav ref-nav" data-kg-header>
    <button type="button" class="burger" data-kg-menu-toggle aria-expanded="false" aria-label="باز کردن منو">
        <i></i><i></i>
    </button>
    <a class="brand" href="{{ route('home') }}">
        @if(!empty($media['brand_logo']['url']))
            <img class="ref-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="{{ $media['brand_logo']['alt_text'] ?: $settings->name }}">
        @endif
        <span>{{ $settings->name }}</span>
    </a>
    <nav class="ref-nav-links" data-kg-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>خانه</a>
        <a href="{{ route('public.menu') }}" @if(request()->routeIs('public.menu')) aria-current="page" @endif>منو</a>
        <a href="{{ route('public.reservations.create') }}" @if(request()->routeIs('public.reservations.create')) aria-current="page" @endif class="cta">رزرو میز</a>
        @guest
            <a href="{{ route('customer.login') }}">ورود مشتری</a>
        @else
            @can('access-customer')
                <a href="{{ route('customer.dashboard') }}" @if(request()->routeIs('customer.dashboard')) aria-current="page" @endif>حساب من</a>
            @endcan
        @endguest
    </nav>
</header>