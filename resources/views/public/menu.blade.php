<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4efe6">
    <meta name="description" content="منوی به‌روز {{ $settings->name }} با قیمت‌ها، افزودنی‌ها و جزئیات محصولات.">
    <title>منو | {{ $settings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-site public-menu-page">
    <div class="site-topline"><span>هر انتخاب خوب، از شناختن طعم‌ها شروع می‌شود.</span><span class="site-topline-right"><i></i> منوی به‌روز کافه</span></div>
    @include('public.partials.nav')
    <main>
        <section class="menu-page-hero cup-stop" id="home" data-cup-stop>
            <div class="menu-page-hero-copy"><span class="section-kicker">THE MENU / MADE WITH CARE</span><h1>منو برای<br><em>حال خوب تو.</em></h1><p>طعم‌ها، ترکیب‌ها و قیمت‌ها؛ شفاف و ساده، تا با خیال راحت انتخاب کنی.</p><a class="button button-dark" href="#catalog">رفتن به خوراکی‌ها <span>↓</span></a></div>
            <div class="menu-page-hero-visual"><img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1100&q=85" alt="فنجان قهوه تازه" fetchpriority="high"><span class="menu-hero-stamp">MADE<br>FOR YOUR<br>MOOD ✳</span></div>
        </section>
        <div class="menu-jumpbar" aria-label="رفتن به دسته‌بندی">
            @forelse($categories as $category)
                <a href="#category-{{ $category->id }}">{{ $category->name }} <span>{{ $category->products->count() }}</span></a>
            @empty
                <span>منو در حال تکمیل است</span>
            @endforelse
        </div>
        <section class="public-catalog section-wrap cup-stop" id="catalog" data-cup-stop>
            @forelse($categories as $category)
                <section class="public-category" id="category-{{ $category->id }}" data-cup-stop>
                    <header class="public-category-heading"><div><span class="section-kicker">CATEGORY / {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $category->name }}</h2></div><span class="public-category-count">{{ $category->products->count() }} انتخاب</span></header>
                    <div class="public-food-grid">
                        @foreach($category->products as $product)
                            <article class="public-food-card reveal-card">
                                <div class="public-food-image">
                                    @if($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <div class="food-image-placeholder food-placeholder-{{ $loop->iteration % 4 }}"><span>{{ ['☕','🥐','🍰','🥪'][$loop->index % 4] }}</span><small>{{ $settings->name }}</small></div>
                                    @endif
                                    @if($product->preparation_time_minutes)<span class="prep-time">◷ {{ $product->preparation_time_minutes }} دقیقه</span>@endif
                                </div>
                                <div class="public-food-info">
                                    <div class="public-food-title"><h3>{{ $product->name }}</h3><b>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></b></div>
                                    @if($product->description)<p>{{ $product->description }}</p>@endif
                                    @if($product->ingredients)<div class="public-food-line"><span>ترکیبات</span><p>{{ $product->ingredients }}</p></div>@endif
                                    @if(is_array($product->allergens) && count($product->allergens))<div class="public-food-allergens"><b>آلرژن‌های اعلام‌شده:</b> {{ implode('، ', $product->allergens) }}<small>برای حساسیت غذایی، پیش از سفارش با کارکنان هماهنگ کن.</small></div>@endif
                                    @if(is_array($product->dietary_tags) && count($product->dietary_tags))<div class="public-food-tags">@foreach($product->dietary_tags as $tag)<span>{{ $tag }}</span>@endforeach</div>@endif
                                    @if($product->options->isNotEmpty())<div class="public-option-list"><span class="public-option-label">سایز و افزودنی</span>@foreach($product->options as $option)<div><span>{{ $option->name }} <small>{{ ($option->type instanceof \\BackedEnum ? $option->type->value : $option->type) === 'size' ? 'سایز' : 'افزودنی' }}</small></span><b>@if($option->price > 0)+{{ number_format($option->price) }} {{ $settings->currency_label }}@else رایگان @endif</b></div>@endforeach</div>@endif
                                    @if($product->calories !== null)<div class="public-food-calories">{{ number_format($product->calories) }} کیلوکالری در هر وعده <span>· فقط در صورت ثبت اطلاعات تأییدشده</span></div>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="public-catalog-empty"><span>✳</span><h2>منو در حال آماده‌شدن است.</h2><p>وقتی محصولات فعال در پنل ثبت شوند، اینجا نمایش داده می‌شوند.</p><a href="{{ route('admin.login') }}" class="text-link">ورود کارکنان برای مدیریت منو <span>↗</span></a></div>
            @endforelse
            <div class="catalog-disclaimer"><span>✳</span><p>ترکیبات، آلرژن‌ها و اطلاعات تغذیه‌ای صرفاً بر اساس داده‌های واردشده توسط کافه نمایش داده می‌شوند. اگر آلرژی یا محدودیت غذایی داری، قبل از سفارش حتماً جزئیات را با کارکنان بررسی کن.</p></div>
        </section>
        <section class="menu-cta-band cup-stop" data-cup-stop><div><span class="section-kicker">YOUR LITTLE PAUSE</span><h2>طعم بعدیِ موردعلاقه‌ات<br>شاید همین‌جا باشد.</h2></div><a href="{{ route('home') }}#visit" class="button button-cream">ملاقات در کافه <span>↗</span></a></section>
    </main>
    @include('public.partials.footer')
    @include('public.partials.traveling-cup')
</body>
</html>