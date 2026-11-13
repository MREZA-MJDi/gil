<header class="nav ref-nav" data-kg-header>
    <button type="button" class="burger" data-kg-menu-toggle aria-expanded="false" aria-label="باز کردن منو">
        <i></i><i></i>
    </button>
    <a class="brand" href="{{ route('home') }}">{{ $settings->name }}</a>
    <nav class="ref-nav-links" data-kg-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>خانه</a>
        <a href="{{ route('public.menu') }}" @if(request()->routeIs('public.menu')) aria-current="page" @endif>منو</a>
        <a href="{{ route('public.reservations.create') }}" @if(request()->routeIs('public.reservations.create')) aria-current="page" @endif class="cta">رزرو میز</a>
    </nav>
</header>