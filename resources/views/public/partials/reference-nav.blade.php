<div class="page-nav" data-kg-header>
    <button type="button" class="burger" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-reference-nav" aria-label="باز کردن منو"><i></i><i></i></button>
    <a class="brand" href="{{ route('home') }}">
        @if(!empty($media['brand_logo']['url']))<img class="reference-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="">@endif
        {{ $settings->name ?: 'خانه گیلاسی' }}
    </a>
    <nav class="page-nav-links" id="kg-reference-nav" data-kg-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>خانه</a>
        <a href="{{ route('public.menu') }}" @if(request()->routeIs('public.menu')) aria-current="page" @endif>منو و سفارش</a>
        <a href="{{ route('home') }}#about">درباره ما</a>
        <a href="{{ route('public.reservations.create') }}" class="cta" @if(request()->routeIs('public.reservations.*')) aria-current="page" @endif>رزرو میز</a>
        @if($settings->phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->phone) }}">تماس</a>
        @endif
        @auth
            @if(auth()->user()->is_admin)
                <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                <form class="reference-nav-logout" method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit">خروج</button>
                </form>
            @endif
        @else
            <a href="{{ route('admin.login') }}">ورود کارکنان</a>
        @endauth
    </nav>
</div>