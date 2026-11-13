<div class="page-nav" data-kg-header>
    <button type="button" class="burger" data-kg-menu-toggle aria-expanded="false" aria-controls="kg-reference-nav" aria-label="باز کردن منو"><i></i><i></i></button>
    <a class="brand" href="{{ route('home') }}">
        @if(!empty($media['brand_logo']['url']))<img class="reference-brand-logo" src="{{ $media['brand_logo']['url'] }}" alt="">@endif
        {{ $settings->name ?: 'خانه گیلاسی' }}
    </a>
    <nav class="page-nav-links" id="kg-reference-nav" data-kg-nav aria-label="ناوبری اصلی">
        <a href="{{ route('home') }}">خانه</a>
        <a href="{{ route('public.menu') }}">منو</a>
        <a href="{{ route('home') }}#about">درباره ما</a>
        <a href="{{ route('public.reservations.create') }}" class="cta">رزرو میز</a>
        @auth
            @if(auth()->user()->is_admin)<a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>@endif
        @else
            <a href="{{ route('admin.login') }}">ورود کارکنان</a>
        @endauth
    </nav>
</div>