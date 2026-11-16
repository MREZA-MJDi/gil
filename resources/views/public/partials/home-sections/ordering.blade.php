<section class="gil-ordering section-wrap" id="moments">
    <div class="gil-section-heading">
        <div><span class="gil-eyebrow">راه‌های سفارش</span><h2>هرجا هستی،<br><em>ساده سفارش بده.</em></h2></div>
        <div class="gil-section-heading-side"><p>سه راه روشن برای تجربهٔ {{ $settings->name }}؛ بدون اینکه بین چند مسیر نامشخص سردرگم شوی.</p></div>
    </div>
    <div class="gil-ordering-grid">
        <a href="{{ route('public.menu') }}" class="gil-order-card gil-order-card-dark"><span class="gil-order-icon">↗</span><span class="gil-order-number">۰۱ / سفارش مستقیم</span><h3>سفارش از سایت</h3><p>منو را ببین، گزینه‌ها را انتخاب کن و سفارش بیرون‌بر را ثبت کن. مبلغ نهایی سمت سرور محاسبه می‌شود.</p><b>رفتن به منو <span>↗</span></b></a>
        <article class="gil-order-card gil-order-card-light"><span class="gil-order-icon">▦</span><span class="gil-order-number">۰۲ / سفارش با کد میز</span><h3>سفارش از میز</h3><p>QR همان میز را اسکن کن؛ سفارش به همان میز وصل می‌شود تا کارکنان بتوانند آن را پیگیری کنند.</p><span class="gil-order-card-note">درخواست پرداخت از این صفحه به معنی پرداخت موفق نیست.</span></article>
        <a href="{{ route('public.reservations.create') }}" class="gil-order-card gil-order-card-olive"><span class="gil-order-icon">◷</span><span class="gil-order-number">۰۳ / رزرو و مناسبت</span><h3>رزرو و جشن‌ها</h3><p>برای میز، جشن تولد، مشاوره، دورهمی خصوصی یا کترینگ درخواست بفرست؛ تیم کافه جزئیات را تأیید می‌کند.</p><b>ثبت درخواست رزرو <span>↗</span></b></a>
    </div>
</section>
