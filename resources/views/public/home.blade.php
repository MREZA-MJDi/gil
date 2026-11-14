<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171614">
    <meta name="description" content="{{ $settings->name }}؛ منوی واقعی کافه، سفارش مستقیم، QR میز و هماهنگی رزرو و مراسم.">
    <title>{{ $settings->name }} | قهوه، غذا، تجربه</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-home-v2">
    <div class="site-topline">
        <span>قهوهٔ دقیق · غذای تازه · میزبانی باحوصله</span>
        <span class="site-topline-right"><i></i> هر انتخاب، با جزئیات روشن</span>
    </div>
    @include('public.partials.nav')
    <main class="gil-home">
        @include('public.partials.home-sections.hero')

        @include('public.partials.home-sections.category-strip')

        @include('public.partials.home-sections.featured-menu')

        @include('public.partials.home-sections.experience')

        @include('public.partials.home-sections.ordering')

        @include('public.partials.home-sections.event-banner')

        @include('public.partials.home-sections.gallery')

        @include('public.partials.home-sections.final-cta')
    </main>
    @include('public.partials.footer')
</body>
</html>
