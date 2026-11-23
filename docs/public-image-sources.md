# منابع تصاویر پیش‌فرض صفحات عمومی

این فهرست URLهای مستقیم CDN تصاویر جایگزین (fallback) را که در Blade صفحات عمومی استفاده شده‌اند ثبت می‌کند. اگر برای هر slot در مدیریت رسانه تصویر واقعی مجموعه بارگذاری شود، تصویر بارگذاری‌شده به‌جای fallback نمایش داده می‌شود. تصاویر محصولات نیز از `image_url` خود محصول می‌آیند و منبع ثابت این فهرست نیستند.

| کاربرد / جایگاه رسانه | موضوع تصویر | URL منبع |
|---|---|---|
| تصویر اصلی صفحهٔ خانه، دسته‌بندی و کارت غذای پیش‌فرض | غذا و چیدمان | [Unsplash CDN](https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=2400&q=90) |
| جایگزین دسته‌بندی خالی | قهوه | [Unsplash CDN](https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=700&q=82) |
| تصویر تجربه / غذای رستوران | فضای سرو غذا | [Unsplash CDN](https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1200&q=88) |
| تصویر تجربه / آماده‌سازی قهوه | قهوه | [Unsplash CDN](https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=85) |
| بنر مراسم در خانه و تصویر رزرو | چیدمان مراسم | [Unsplash CDN](https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=2200&q=88) |
| گالری `gallery_1` — فضای کافه | فضای داخلی رستوران | [Unsplash CDN](https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1300&q=88) |
| گالری `gallery_2` — غذا و نوشیدنی | غذاهای آمادهٔ سرو | [Unsplash CDN](https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=900&q=88) |
| گالری `gallery_3` — مناسبت‌ها | چیدمان میز و دورهمی | [Unsplash CDN](https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=900&q=88) |
| تصویر اصلی منوی بیرون‌بر | فنجان قهوه | [Unsplash CDN](https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1100&q=85) |

## نکات انتشار

- این‌ها URLهای مستقیم تصویر هستند، نه صفحهٔ جزئیات هر عکس در Unsplash.
- پیش از انتشار تجاری، شرایط مجوز جاری منبع و هر الزام احتمالی اعتباردهی را بررسی کنید.
- سه slot گالری در داشبورد رسانه با همان کاربردهای `gallery_1` (فضای کافه)، `gallery_2` (غذا و نوشیدنی) و `gallery_3` (مناسبت‌ها) به سه تصویر گالری صفحهٔ خانه متصل‌اند.
- برای تصاویر اختصاصی رستوران، slotهای رسانه‌ای مانند `hero`، `menu_cover`، `story_main`، `story_small` و `gallery_1` تا `gallery_3` را از داشبورد رسانه با فایل‌های مجاز و متن جایگزین مناسب پر کنید.
- متن جایگزین ثبت‌شده برای هر تصویر گالری در خروجی صفحه استفاده می‌شود؛ اگر ثبت نشده باشد، متن جایگزین فارسی پیش‌فرض نمایش داده می‌شود.
- منبع عکس‌های اختصاصی باید در فرایند تحویل محتوا ثبت شود؛ بارگذاری در پنل به‌تنهایی مالکیت یا مجوز استفاده را اثبات نمی‌کند.
