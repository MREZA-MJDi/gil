<section class="gil-menu-feature section-wrap" id="menu">
    <div class="gil-section-heading">
        <div><span class="gil-eyebrow">انتخاب‌های امروز</span><h2>انتخاب‌های<br><em>خوش‌طعم</em> ما.</h2></div>
        <div class="gil-section-heading-side"><p>این بخش از محصولات فعال پایگاه داده ساخته می‌شود؛ قیمت و گزینه‌ها همان اطلاعاتی‌اند که در پنل ثبت شده‌اند.</p><div class="gil-menu-side-actions"><a class="gil-text-link" href="{{ route('public.menu') }}">دیدن منوی کامل <span>↗</span></a><div class="gil-carousel-controls" aria-label="حرکت بین محصولات"><button type="button" data-food-carousel="prev" aria-label="محصولات قبلی">‹</button><button type="button" data-food-carousel="next" aria-label="محصولات بعدی">›</button></div></div></div>
    </div>
    <div class="gil-food-grid">
        @forelse ($featuredProducts as $index => $product)
            <article class="gil-food-card">
                <a href="{{ route('public.menu') }}" class="gil-food-photo">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <img src="https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=900&q=85" alt="غذای آماده سرو" loading="lazy">
                    @endif
                    <span class="gil-food-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="gil-food-open">مشاهده <b>↗</b></span>
                </a>
                <div class="gil-food-meta"><div><h3>{{ $product->name }}</h3><p>{{ $product->description ?: 'برای ترکیبات و گزینه‌های قابل انتخاب، منوی کامل را ببین.' }}</p></div><strong>{{ number_format($product->base_price) }} <small>{{ $settings->currency_label }}</small></strong></div>
                @if ($product->options->isNotEmpty())
                    <div class="gil-food-options">@foreach ($product->options->take(2) as $option)<span>{{ $option->name }} @if ($option->price > 0) <i>+{{ number_format($option->price) }}</i>@endif</span>@endforeach</div>
                @endif
            </article>
        @empty
            <div class="gil-empty-menu"><span>✳</span><h3>منو در حال آماده‌شدن است.</h3><p>با ثبت محصولات فعال در داشبورد، غذاها و نوشیدنی‌ها به‌صورت خودکار اینجا نمایش داده می‌شوند.</p><a class="gil-text-link" href="{{ route('admin.login') }}">ورود کارکنان برای تکمیل منو <span>↗</span></a></div>
        @endforelse
    </div>
    <div class="gil-menu-footer"><span>اطلاعات مواد اولیه و آلرژن‌ها بر اساس داده‌ای است که کافه ثبت کرده است.</span><a class="gil-button gil-button-dark" href="{{ route('public.menu') }}">منوی کامل و سفارش آنلاین <span>↗</span></a></div>
</section>
