<section class="gil-event-banner" id="story">
    <img src="{{ $media['gallery_3']['url'] ?? 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=2200&q=88' }}" alt="{{ filled($media['gallery_3']['alt_text'] ?? null) ? $media['gallery_3']['alt_text'] : 'میز چیده‌شده برای یک مناسبت' }}" loading="lazy">
    <div class="gil-event-shade"></div>
    <div class="gil-event-content"><span class="gil-eyebrow">تولد · دورهمی خصوصی · پذیرایی</span><h2>برای خاطره‌ای<br>که <em>می‌ماند</em>، میز بچین.</h2><p>تعداد مهمان، زمان، بودجهٔ تقریبی و جزئیات مراسم را بفرست. درخواستت در پنل ثبت می‌شود و هماهنگی نهایی با تیم کافه انجام خواهد شد.</p><a class="gil-button gil-button-gold" href="{{ route('public.reservations.create') }}">شروع برنامه‌ریزی <span>↗</span></a></div>
</section>
