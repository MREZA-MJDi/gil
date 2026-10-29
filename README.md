<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

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
- Configure a private \`ORDER_WEBHOOK_SECRET\` with at least 32 characters. Send \`X-Gil-Signature: sha256=<hex HMAC-SHA256 of the exact raw request body>\`.
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

Demo prices are stored in the selected database currency unit. The demo dataset uses the currency unit selected in restaurant settings. Set the unit to تومان or ریال before inserting products/orders; the seeder scales demo product prices, option prices, ingredient unit costs, cash balances, expenses and reservation budgets accordingly. For safety, the dashboard does not let staff switch the currency after products or orders exist, because historical money values are never silently relabelled or converted. The selected unit is used throughout the dashboard, website checkout, printed receipts and QR menu.
