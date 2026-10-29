# خانه گیلاسی - پنل عملیات کافه و رستوران

سامانه Laravel + React برای مدیریت منو، سفارش‌ها، پرداخت و صندوق، موجودی، میز و QR، رزروها، هزینه‌ها، بازخورد و گزارش‌های عملیاتی. تجربه عمومی سایت فارسی و راست‌چین است. تقویم ورودی رزرو و گزارش‌ها شمسی نمایش داده می‌شود و تاریخ استاندارد برای backend ارسال می‌شود.

## نیازمندی‌ها

- PHP 8.2+ و Composer
- Node.js 22+ و npm
- SQLite برای راه‌اندازی ساده؛ دیتابیس‌های دیگر بر اساس تنظیم محیط قابل پیکربندی‌اند.

## راه‌اندازی محلی

    cp .env.example .env
    composer install
    npm ci
    php artisan key:generate
    mkdir -p database
    touch database/database.sqlite

در فایل .env شماره موبایل و رمز مدیر را تنظیم کن:

    APP_NAME="خانه گیلاسی"
    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://localhost:8086
    APP_TIMEZONE=Asia/Tehran
    DB_CONNECTION=sqlite

    ADMIN_NAME="مدیر خانه گیلاسی"
    ADMIN_PHONE=09121234567
    ADMIN_PASSWORD="replace-with-a-unique-password-14-chars-or-more"
    # Optional: only enable staff registration if an invitation key is required.
    ADMIN_REGISTRATION_KEY=

سپس:

    php artisan migrate
    php artisan db:seed
    npm run build
    php artisan serve --host=0.0.0.0 --port=8086

صفحه اصلی: http://localhost:8086/  
ورود کارکنان/مدیر: http://localhost:8086/admin/login  
ثبت‌نام با کلید دعوت: http://localhost:8086/admin/register  
پنل پس از ورود: http://localhost:8086/admin

## Seeder و داده‌ها

DatabaseSeeder ابتدا مدیر صریحاً پیکربندی‌شده و سپس تنظیمات پایه مجموعه را ایجاد می‌کند. seeder هیچ محصول، دسته، قیمت، موجودی یا تراکنش ساختگی نمی‌سازد. میزها فقط وقتی از نام‌های واقعی در RESTAURANT_TABLE_NAMES تنظیم شده باشند ایجاد می‌شوند. کاتالوگ واقعی را از داشبورد وارد کن یا از یک فایل دادهٔ تأییدشدهٔ کسب‌وکار seed کن.

## واحد پول و تاریخ

- ورود مبلغ هزارگان‌دار است.
- واحد نمایشی تومان/ریال در تنظیمات قابل انتخاب است. تغییر برچسب، مقدار ذخیره‌شده را تبدیل نمی‌کند؛ همه مبالغ باید در یک واحد یکنواخت ثبت شوند.
- تاریخ و زمان رزرو/گزارش به شمسی انتخاب یا نمایش داده می‌شود؛ backend مقدار استاندارد ISO/Gregorian را اعتبارسنجی و ذخیره می‌کند.
- منطقه زمانی پیش‌فرض محیط کسب‌وکار Asia/Tehran است.

## رزرو عمومی

صفحه اصلی فرم «درخواست رزرو میز» دارد. درخواست مشتری با وضعیت pending ذخیره می‌شود و کد پیگیری نمایش داده می‌شود. تأیید در پنل نیازمند تخصیص میز فعال است؛ تداخل زمانی سمت سرور بررسی می‌شود. idempotency و rate limiting برای کاهش درخواست‌های تکراری فعال‌اند.

## تست‌ها و build

    php artisan test
    npm run test:js
    npm run build

GitHub Actions این مراحل را نیز روی push و pull request اجرا می‌کند.

## استقرار روی پورت 8086

برای تست موقت از دستور زیر استفاده کن:

    php artisan serve --host=0.0.0.0 --port=8086

این سرور توسعه برای production مناسب نیست. در production از Nginx یا وب‌سرور مناسب، PHP-FPM یا سرویس مدیریت‌شده، TLS، APP_DEBUG=false، بکاپ، لاگ و restart خودکار استفاده کن؛ پورت را در firewall فقط برای مبدأهای لازم باز کن.

راهنمای کاربری داشبورد: docs/dashboard-guide-fa.md  
راهنمای استقرار روی سرور و Nginx: docs/deployment-server-fa.md  
نمونه کانفیگ Nginx پورت 8086: deploy/nginx-gil-8086.conf.example
