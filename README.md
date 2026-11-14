# Q Food - پنل عملیات کافه و رستوران

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

    APP_NAME="Q Food"
    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://localhost:8086
    APP_TIMEZONE=Asia/Tehran
    DB_CONNECTION=sqlite

    ADMIN_NAME="مدیر Q Food"
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

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## Advanced restaurant workflows

### Recipes and ingredient stock
- Create base ingredients in **رسپی و مواد اولیه** using base units: \`g\` (grams), \`ml\` (millilitres), or \`pcs\` (pieces). Stock and recipe quantities accept up to three decimal places.
- Attach ingredients and per-item amounts to each menu product. Estimated ingredient cost uses each item's saved cost per base unit.
- Order creation checks and consumes recipe ingredients in the same database transaction. Eligible cancellations restore ingredients using the immutable recipe snapshot captured at the time of sale.
- Product stock tracking and ingredient stock tracking are separate. Products without a recipe continue to use product-level stock only.

### Website orders and public reservations
- Public menu checkout stores takeaway orders as source \`website\`; the order is visible in the admin queue, and it is not marked paid automatically.
- \`/reserve\` accepts table requests, birthdays, consultations, private events, and catering inquiries. Requests start as pending until a staff member confirms capacity and details.

### External order intake bridge
- Signed normalized intake endpoints are available at \`POST /api/integrations/orders/snappfood\` and \`POST /api/integrations/orders/partner\`. This is a trusted adapter contract, **not a claim that Snappfood's live/private API has already been connected**.
- Configure a private \`ORDER_WEBHOOK_SECRET\` with at least 32 characters. Send \`X-QFood-Signature: sha256=<hex HMAC-SHA256 of the exact raw request body>\`.
- External product IDs must be mapped by the adapter to this system's \`product_id\`. The app recalculates prices using its own catalog, deduplicates by external order ID, and does not assume marketplace payment is settled. Production status/refund/settlement synchronization needs official API access and a dedicated adapter.


## Local demo data for full UI testing

The repository includes a local-only demo dataset for testing the application through the browser. It adds Persian menu categories and product images, recipes and ingredients, ten QR-linked tables, orders with multiple channels/statuses, partial/full payments and a sample refund, cash-shift activity, expenses, reservations for birthdays/consultations/catering, print-log entries, and customer feedback. Demo customer records are clearly identifiable by their sample names or \`DEMO\` references. Do not run demo seeds against a real restaurant database.

From the project directory, first make sure \`.env\` has \`APP_ENV=local\`, the database name and credentials you intend to use, and admin credentials. Then run:

\`\`\`bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class="Database\\Seeders\\AdminUserSeeder" --force
php artisan db:seed --class="Database\\Seeders\\RestaurantDemoSeeder" --force
npm run build
php artisan optimize:clear
php artisan serve
\`\`\`

Open \`http://127.0.0.1:8000/\` for the public website, \`http://127.0.0.1:8000/menu\` for direct takeaway ordering, \`http://127.0.0.1:8000/reserve\` for booking requests, and \`http://127.0.0.1:8000/admin/login\` for the dashboard. For QR testing, open **میزها و QR** in the dashboard and print or preview a table's own QR. On a phone, \`APP_URL\` must be a LAN-reachable host/IP, not \`localhost\`.

Demo amounts use the selected currency unit. Set `RESTAURANT_CURRENCY_LABEL=تومان` for Toman demo values or `RESTAURANT_CURRENCY_LABEL=ریال` for Rial demo values before creating the first catalog/orders. The seeder scales demo prices, option prices, ingredient unit costs, cash balances, expenses, and reservation budgets consistently. The dashboard blocks changing currency after any products or orders exist because historical money must never be silently relabelled or converted. The selected unit is shown in dashboard totals, website checkout, printed receipts, and QR menu.
