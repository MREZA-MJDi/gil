# Backend implementation and production readiness

This repository is being built as a real restaurant operations system, not a UI-only mock.

## Core rules
- Server is the source of truth for prices, tax, service charge, payments, refunds, and reports.
- Each order item snapshots its product name, unit price, option labels/prices, quantity, and line total.
- Order preparation state and payment state are independent.
- Order, payment, refund, shift and cash movement operations use transactions, row locks where concurrent updates are possible, and idempotency keys tied to a canonical payload hash.
- Financial events and audit history are append-only. Corrections must be represented by new events, not rewriting historical payments/refunds.
- Printing an invoice, receipt, or kitchen slip is not a payment action.
- Online payments remain disabled until a real gateway and verified callback/webhook flow is implemented.
- QR tokens are unguessable capabilities scoped to the table's menu; never accept an arbitrary client-supplied table ID for QR orders.
- All admin routes require authentication and admin authorization.

## Run locally
1. `composer install`
2. Copy `.env.example` to `.env`, configure a local database, and set a strong `ADMIN_EMAIL`, `ADMIN_PASSWORD` (at least 14 characters), and `ADMIN_NAME`.
3. `php artisan key:generate`
4. `npm install`
5. `php artisan migrate`
6. `php artisan db:seed --class="Database\\Seeders\\AdminUserSeeder"`
7. `npm run dev` and `php artisan serve`
8. Visit `/admin/login`, then `/admin`.

## Verify before use
- `npm run build`
- `php artisan route:list --path=admin`
- `php artisan test`
- Run race-condition and idempotency tests against the same relational database engine planned for production. Do not rely solely on SQLite for row-locking behavior.
- Add CI for PHP lint/Pint, feature tests, and Vite production build.

## Known integration boundary
The repository started as a Laravel skeleton. The provided domain ZIPs are a starting point, but must be reconciled with the final schema, namespaces, controllers, validation and tests. The dashboard's client-side example data must not be used as financial source-of-truth.
