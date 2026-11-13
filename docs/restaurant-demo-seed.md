# Restaurant demo data seeding

## Included sample dataset

`RestaurantDemoSeeder` is idempotent and uses stable `seed_key` / reference keys. It creates or reuses:
- 4 menu categories and 20 sample products with options and stock quantities;
- ingredient records and product recipe links;
- 10 QR-enabled restaurant tables;
- 12 example orders across QR, staff/counter, website and partner channels, including new, preparing, ready, delivered, cancelled and partially settled examples;
- sample payments, a partial refund, print-attempt logs and cash movements;
- a sample cash shift, reservations, expenses and customer feedback.

All sample orders have an internal note stating that they are demo data. Sample payment records are not real money received.

## Production caution

Production seeding is disabled by default. Run the demo dataset only when you explicitly want a populated test/demo workspace. These examples are persisted in the real `gil_db` schema and will appear in order lists, cash-shift history and sales/report screens; they are not automatically hidden from operational reporting.

From the project root on the server, run this one-command opt-in:

```bash
ALLOW_PRODUCTION_DEMO_SEED=true php artisan db:seed --class='Database\\Seeders\\RestaurantDemoSeeder' --force
```

The opt-in is process-local and is not written to `.env`. Without the flag the seeder throws in production. Re-running it does not duplicate seeded products, tables, reservations or orders and preserves dashboard edits to seeded records.

For a completely fresh/demo environment, the regular `DatabaseSeeder` runs the admin and foundation seeders first. In production that full seeding path also requires `ALLOW_PRODUCTION_DEMO_SEED=true` explicitly:

```bash
ALLOW_PRODUCTION_DEMO_SEED=true php artisan db:seed --force
```

Do not run `migrate:fresh`, `migrate:refresh` or drop the database to apply this dataset.
