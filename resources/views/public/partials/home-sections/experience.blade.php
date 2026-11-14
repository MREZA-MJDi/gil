<section class="gil-experience">
    <div class="gil-experience-images">
        <div class="gil-experience-image gil-experience-image-main"><img src="{{ $media['story_main']['url'] ?? 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1200&q=88' }}" alt="غذای تازه در فضای رستوران" loading="lazy"><span>از آشپزخانه تا میز تو</span></div>
        <div class="gil-experience-image gil-experience-image-small"><img src="{{ $media['story_small']['url'] ?? 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=85' }}" alt="قهوه در حال آماده‌شدن" loading="lazy"><span>قهوه با دقت</span></div>
        <div class="gil-image-note"><b>جزئیات مهم‌اند</b><span>ترکیب خوب، انتخاب روشن، سرو باحوصله.</span></div>
    </div>
    <div class="gil-experience-copy">
        <span class="gil-eyebrow">فراتر از یک قرار</span>
        <h2>اینجا برای<br>یک تجربهٔ <em>کامل</em><br>آمده‌ای.</h2>
        <p>برای یک قهوهٔ کوتاه، یک وعدهٔ کامل، قرار دوستانه یا برنامه‌ریزی یک مناسبت. منو و مسیر سفارش ساده است تا تمرکزت روی لحظه‌ای باشد که برایش آمده‌ای.</p>
        <div class="gil-experience-points">
            <div><span>01</span><p><b>انتخاب شفاف</b><small>قیمت، ترکیبات ثبت‌شده و افزودنی‌ها در منو</small></p></div>
            <div><span>02</span><p><b>سفارش راحت</b><small>از میز با QR یا مستقیم از صفحهٔ منو</small></p></div>
            <div><span>03</span><p><b>برنامه‌ریزی مناسبت</b><small>درخواست رزرو میز، تولد و هماهنگی مراسم</small></p></div>
        </div>
        <a class="gil-text-link" href="{{ route('public.reservations.create') }}">برای قرار بعدی برنامه‌ریزی کن <span>↗</span></a>
    </div>
</section>
