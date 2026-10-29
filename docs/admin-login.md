# Admin account setup

The repository intentionally does not contain a fixed admin password. GitHub repository access alone cannot insert a row into a remote runtime database. Create the admin account in the same database configured by the application's private .env file.

For a disposable local environment only (`APP_ENV=local`), the seeder permits a short development password so you can preview the UI quickly. This allowance is disabled outside local mode; never expose the local demo account on a public network or production server.

## 1. Configure the account

Set these values in the server's private .env file. Use a real email you control and a unique password of at least 14 characters.

    ADMIN_NAME="مدیر خانه گیلاسی"
    ADMIN_EMAIL=you@example.com
    ADMIN_PASSWORD="replace-with-a-unique-password-at-least-14-chars"

Do not commit .env, paste the real password into chat, or reuse a production password in tests.

## 2. Apply schema and seed the account

From the repository root, using the same environment and database as the web app:

    php artisan config:clear
    php artisan migrate --force
    php artisan db:seed --class="Database\\Seeders\\AdminUserSeeder" --force

Or run php artisan db:seed --force; DatabaseSeeder calls AdminUserSeeder. The seeder creates or updates only the account for ADMIN_EMAIL and marks it as an administrator. The password is hashed by Laravel's hashed model cast.

For a new local installation, first run composer install, copy .env.example to .env, generate the app key with php artisan key:generate, configure the database connection, and then run the migration/seed commands above. On a production server, take a database backup and verify .env points to the intended database before running migrations.

## 3. Sign in

Open /admin/login, use the ADMIN_EMAIL and ADMIN_PASSWORD values, then go to /admin. The dashboard and /admin/api/* endpoints require an authenticated user with is_admin = true.

If sign-in fails, check:
- php artisan migrate:status
- whether the web server and CLI process use the same .env and database
- whether the record exists and has is_admin = 1
- whether the email and password values match the private environment configuration

Avoid printing or sharing the admin password while debugging. Do not use a predictable demo account in a public or production environment.
