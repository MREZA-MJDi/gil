# استقرار خانه گیلاسی روی Ubuntu و Nginx (پورت 8086)

این راهنما برای ریپوی اصلی MREZA-MJDi/gil نوشته شده است. هیچ رمز، کلید دعوت یا فایل .env نباید commit شود.

## ۱. پیش‌بررسی؛ قبل از تغییر سرور

    command -v composer
    systemctl is-active nginx
    systemctl is-active mysql
    systemctl is-active php8.5-fpm
    ls -l /run/php/
    ss -ltnp | grep -E ':8086([[:space:]]|$)' || echo "8086 appears free"

PHP CLI نصب بودن، وجود PHP-FPM را تضمین نمی‌کند. اگر php8.5-fpm فعال نیست یا socket آن وجود ندارد، هنوز Nginx را فعال نکن؛ ابتدا بسته/سرویس FPM سازگار با PHP 8.5 را بررسی کن. در کانفیگ Nginx، fastcgi_pass باید دقیقاً با socket موجود در /run/php/ مطابقت داشته باشد.

## ۲. دریافت نسخه main

این دستورها فقط مسیر جدید /var/www/gil را ایجاد می‌کنند و پروژه‌های دیگر را تغییر نمی‌دهند:

    cd /var/www
    git clone https://github.com/MREZA-MJDi/gil.git gil
    cd /var/www/gil
    git status --short --branch
    git log -1 --oneline

اگر /var/www/gil از قبل وجود دارد، کلون را دوباره اجرا نکن؛ ابتدا مسیر و تغییرات محلی را بررسی کن.

## ۳. ساخت دیتابیس اختصاصی MySQL

ابتدا بررسی کن نام gil_db قبلاً برای برنامه‌ای دیگر استفاده نشده باشد:

    mysql -e "SHOW DATABASES LIKE 'gil_db';"

اگر پایگاه داده موجود نیست، یک رمز قوی محلی تولید و در همان نشست برای دیتابیس استفاده کن. مقدار رمز را با دیگران به اشتراک نگذار:

    DB_PASS="$(openssl rand -hex 32)"
    mysql <<SQL
    CREATE DATABASE gil_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'gil_app'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
    GRANT ALL PRIVILEGES ON gil_db.* TO 'gil_app'@'127.0.0.1';
    FLUSH PRIVILEGES;
    SQL
    printf 'DB_PASSWORD=%s\n' "$DB_PASS"
    unset DB_PASS

رمز نمایش‌داده‌شده را در Password Manager نگه دار و فقط در فایل .env پروژه قرار بده. اگر ساخت دیتابیس/کاربر خطا داد، دستور را با IF NOT EXISTS یا ALTER USER کورکورانه تکرار نکن؛ ابتدا وضعیت موجود را بررسی کن.

## ۴. نصب وابستگی‌های پروژه و تنظیم محیط

    cd /var/www/gil
    composer install --no-dev --prefer-dist --optimize-autoloader
    npm ci
    cp .env.example .env
    nano .env

در .env این مقادیر را با داده‌های واقعی خودت کامل کن:

    APP_NAME="خانه گیلاسی"
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=http://45.149.79.155:8086
    APP_TIMEZONE=Asia/Tehran

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=gil_db
    DB_USERNAME=gil_app
    DB_PASSWORD=رمز-تولیدشده-دیتابیس

    ADMIN_NAME="مدیر خانه گیلاسی"
    ADMIN_PHONE=شماره-موبایل-واقعی-مدیر
    ADMIN_PASSWORD=رمز-قوی-که-خودت-تولید-می‌کنی
    ADMIN_EMAIL=
    ADMIN_REGISTRATION_KEY=

    RESTAURANT_NAME="نام واقعی مجموعه"
    RESTAURANT_PHONE=
    RESTAURANT_ADDRESS=
    RESTAURANT_CURRENCY_LABEL=تومان
    RESTAURANT_TABLE_NAMES=

- ADMIN_PHONE شناسه ورود به داشبورد است. شماره ایران می‌تواند با 09... یا +989... باشد.
- ADMIN_PASSWORD باید حداقل ۱۴ نویسه داشته باشد؛ رمز پیش‌فرضی در مخزن وجود ندارد.
- برای تولید رمز مدیر، از Password Manager یا openssl rand -hex 24 استفاده کن و آن را محرمانه نگه دار.
- ADMIN_REGISTRATION_KEY را خالی بگذار تا ثبت‌نام عمومی غیرفعال بماند. اگر لازم داری، یک کلید تصادفی بلند تنظیم کن.
- RESTAURANT_TABLE_NAMES فقط فهرست میزهای واقعی را به‌صورت جداشده با کاما می‌پذیرد؛ مثلاً میز سالن,میز تراس. اگر خالی باشد، هیچ میزی فرضی ساخته نمی‌شود.
- Seeder دیگر محصول/قیمت ساختگی نمی‌سازد؛ کاتالوگ واقعی را پس از ورود از داشبورد تعریف کن.
- APP_KEY را با php artisan key:generate بساز و در deployهای بعدی تغییر نده.

بعد از تنظیم .env:

    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed --force
    npm run build
    php artisan storage:link

اگر php artisan key:generate را برای بار دوم روی سیستم فعال اجرا کردی، بررسی کن کلید موجود را ناخواسته جایگزین نکنی؛ تغییر APP_KEY می‌تواند نشست‌ها و داده‌های رمزگذاری‌شده را تحت تأثیر قرار دهد. در نصب تازه، این دستور را فقط یک بار اجرا کن.

مجوز نوشتن را فقط به مسیرهای لازم بده:

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache

## ۵. فعال کردن Nginx برای پورت 8086

فایل deploy/nginx-gil-8086.conf.example را بازبینی کن. پیش از فعال‌سازی مطمئن شو fastcgi_pass با socket واقعی PHP-FPM مطابقت دارد.

    nano /etc/nginx/sites-available/gil-8086

محتوای فایل را از نمونه ریپو کپی کن. سپس:

    ln -s /etc/nginx/sites-available/gil-8086 /etc/nginx/sites-enabled/gil-8086
    nginx -t
    systemctl reload nginx

اگر nginx -t خطا داد، reload نکن و خطا را بررسی کن. قبل از ایجاد symlink نیز بررسی کن نام این فایل از قبل وجود نداشته باشد.

## ۶. اعتبارسنجی بعد از استقرار

    cd /var/www/gil
    php artisan test
    npm run test:js
    curl -sS -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8086/
    curl -sS -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8086/admin/login

صفحه ورود: http://45.149.79.155:8086/admin/login
صفحه اصلی: http://45.149.79.155:8086/

ورود مدیر با همان ADMIN_PHONE و ADMIN_PASSWORD انجام می‌شود که در فایل محلی .env تنظیم کرده‌ای. این مقادیر در GitHub یا سورس پروژه ذخیره نمی‌شوند.

اگر از بیرون سرور به پورت دسترسی نیست، ابتدا firewall و قوانین شبکه ارائه‌دهنده را بررسی کن؛ فقط در صورت نیاز و آگاهی از سیاست امنیتی، پورت 8086 را باز کن. برای استفاده عمومی، TLS/HTTPS و دامنه توصیه می‌شود.

## ۷. خطاهای رایج

- 502 Bad Gateway: معمولاً PHP-FPM فعال نیست یا مسیر fastcgi_pass اشتباه است؛ systemctl status php8.5-fpm و ls -l /run/php/ را بررسی کن.
- 500 Internal Server Error: storage/logs/laravel.log و /var/log/nginx/gil-8086.error.log را بررسی کن؛ APP_DEBUG را در production روشن نکن.
- Access denied MySQL: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD و مجوز user را بررسی کن.
- Vite manifest not found: npm ci و npm run build را در /var/www/gil دوباره اجرا و وجود public/build/manifest.json را بررسی کن.
