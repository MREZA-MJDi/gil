# Customer QR menu and day-to-day operations

## Admin account

There is no hard-coded admin username/password in the repository. The sample ADMIN_EMAIL in .env.example is a placeholder, and ADMIN_PASSWORD is deliberately blank. Set both values in the private server .env and seed the administrator using the instructions in docs/admin-login.md. Sign in at /admin/login; email is the username.

## Make table QR codes that work on customer phones

1. Set APP_URL to the real HTTPS domain for the live site. For a local test, use a LAN IP/hostname that is reachable from the customer's phone on the same Wi-Fi; do not use localhost or 127.0.0.1 for a printed QR. Clear cached config after changing it.
2. Migrate the database and create an admin account.
3. Sign in to /admin, open Tables and QR, create each table with a unique name, then choose "نمایش / چاپ QR" for that table. The dashboard encodes that table's own URL into a QR image and opens a print-ready A6 label. The customer can scan it with the phone camera.
4. Keep the label physically on the matching table. Rotating that table's QR token intentionally invalidates the old label, so print and replace it.
5. To serve uploaded product photos, run `php artisan storage:link` during deployment and ensure the public storage disk is writable.

Each table has a random 64-character token. The public URL looks like /menu/{token}; its server endpoint verifies that token, checks that the table is active and that the customer menu is enabled, then returns the active catalogue. The order endpoint derives the table from the token instead of trusting a table ID sent by the phone.

## Configure what the customer sees

1. In Settings, enter the restaurant name, address, phone, currency label, tax and service rates, and enable the customer QR menu. Keep tax/service rates at zero if the venue does not charge them.
2. In Menu and Products, create categories, and then add each product. Provide its name, price, category, a clear description, the product photo, ingredients, allergens, dietary labels, calories per serving (only if verified), and an estimated preparation time (if known).
3. Add size choices and extras. A size is presented as a single-choice option; extras can be combined. Each option's additional price is shown to the customer and included in the server-side order calculation.
4. Set the product to Active and its category to active before expecting it to appear publicly. Inactive categories/products are not exposed by the customer menu. Use Unavailable when the item should not be ordered.
5. If stock tracking is appropriate, set an opening quantity and low-stock threshold. An empty quantity means stock tracking is intentionally off for that product.

The QR menu displays the live product name, image, description, base price, selected option prices, customer-provided ingredients/allergen details, dietary tags, calories and preparation time when supplied, plus an estimated subtotal/tax/service/total. The browser estimate is informational; the server recalculates the final order total and validates the active product/options at submission.

All food and allergen details must be entered and verified by the restaurant. The system does not invent ingredients, allergen safety claims, nutrition facts or dietary suitability.

## What happens after a customer scans the QR

1. Customer chooses products/options, checks the amount summary and taps "ثبت سفارش". The order is saved in the database with the scanned table ID. A QR submission is not an online payment.
2. The staff dashboard's Overview, Orders and Cashier screens refresh from the server every 15 seconds; staff can also refresh manually. The new order appears in Orders.
3. Staff moves it through New → Confirmed → Preparing → Ready → Delivered. These actions write order-status history. The kitchen slip can be printed from the order, and the order detail supports invoice/receipt printing after a payment has been recorded.
4. On Cashier, open a shift before recording a cash payment, then record the payment method and any transaction reference. Refunds are separate ledger entries. End the shift by counting cash and recording the difference.
5. On Menu and Products, changes to price, details, options or active status affect the public menu without having to reprint the QR. Reprint the QR only when rotating a token or changing the public site/domain.

## Current boundaries to know before using it commercially

- QR ordering and the customer-facing menu are implemented; customer online payment is not. The order is sent to staff, and payment is recorded at the POS.
- A "card terminal" payment is currently staff-recorded, not automatically verified from the physical terminal. Do not treat the status as bank-confirmed.
- Receipt/invoice/kitchen printing currently uses the browser print dialog. Silent printing and printer hardware status require a local printer bridge; see docs/receipt-printer-integration.md.
- Stock accounting is quantity-per-product, not recipe/BOM-based ingredient deduction. For example, selling a latte does not yet deduct 18 g of coffee or 200 ml of milk from separate ingredient stocks.
- The menu displays calories and allergen labels only when staff provide verified values. It is not a nutrition database or a guarantee against cross-contamination.
- Before going live, use HTTPS, back up the database, configure a reachable APP_URL, run migrations and the admin seeder against the intended database, and test at least one real phone, printer, and cashier workflow.
