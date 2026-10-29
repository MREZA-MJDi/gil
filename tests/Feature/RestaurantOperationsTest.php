<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RestaurantOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Operations Test Admin',
            'email' => 'ops-test@example.test',
            'password' => 'test-only-password-12345',
            'is_admin' => true,
        ]);
        $this->category = Category::query()->create([
            'name' => 'دسته تست',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => 'محصول تست',
            'base_price' => 125000,
            'status' => 'active',
            'sort_order' => 1,
            'stock_quantity' => 5,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_admin_api_requires_authentication(): void
    {
        $this->getJson('/admin/api/dashboard')->assertUnauthorized();
    }

    public function test_admin_dashboard_redirects_guests_to_admin_login_route(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_public_home_and_menu_render_database_catalog(): void
    {
        $this->product->forceFill([
            'name' => 'لاته تست',
            'description' => 'قهوه تازه با شیر',
            'base_price' => 95000,
        ])->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('Pan-Asian Crave')
            ->assertSee('لاته تست')
            ->assertSee('ورود کارکنان');

        $this->actingAs($this->admin)->get('/')
            ->assertOk()
            ->assertSee('پنل مدیریت')
            ->assertSee('خروج')
            ->assertSee(route('admin.logout'), false);

        $this->get('/menu')->assertOk()->assertSee('منو برای')->assertSee('لاته تست');
    }

    public function test_local_admin_seeder_uses_mobile_and_strong_password_credentials(): void
    {
        config([
            'app.env' => 'local',
            'admin.name' => 'Local Test Admin',
            'admin.phone' => '09121230001',
            'admin.email' => null,
            'admin.password' => 'local-test-password-123456',
        ]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdminUserSeeder::class]);

        $this->post('/admin/login', [
            'phone' => '09121230001',
            'password' => 'local-test-password-123456',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs(User::query()->where('phone', '+989121230001')->first());
    }

    public function test_admin_login_works_with_seeded_mobile_configuration(): void
    {
        config([
            'admin.name' => 'Seeded Test Admin',
            'admin.phone' => '+989121230002',
            'admin.email' => null,
            'admin.password' => 'seeded-test-password-123456',
        ]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdminUserSeeder::class]);

        $this->post('/admin/login', [
            'phone' => '09121230002',
            'password' => 'seeded-test-password-123456',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->is_admin);
    }

    public function test_dashboard_endpoint_returns_operational_metrics_from_database(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'registered_sales',
                    'collections_net',
                    'orders_count',
                    'orders_by_status',
                    'open_invoice_amount',
                    'low_stock_count',
                    'today_reservations_count',
                    'active_cash_shift',
                    'recent_orders',
                ],
            ])
            ->assertJsonPath('data.orders_count', 0)
            ->assertJsonPath('data.low_stock_count', 1);
    }

    public function test_order_creation_is_idempotent_and_consumes_stock_once(): void
    {
        $this->actingAs($this->admin);
        $payload = $this->orderPayload(2);
        $headers = ['Idempotency-Key' => 'order-test-key-0001'];

        $first = $this->postJson('/admin/api/orders', $payload, $headers)->assertCreated();
        $second = $this->postJson('/admin/api/orders', $payload, $headers)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(3, $this->product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'order')->count());
        $this->assertSame(1, Order::query()->count());
    }

    public function test_order_cancellation_restores_tracked_stock_and_records_a_movement(): void
    {
        $this->actingAs($this->admin);

        $created = $this->postJson('/admin/api/orders', $this->orderPayload(2), [
            'Idempotency-Key' => 'order-cancel-test-0001',
        ])->assertCreated();
        $orderId = $created->json('data.id');
        $this->assertSame(3, $this->product->fresh()->stock_quantity);

        $this->postJson('/admin/api/orders/'.$orderId.'/cancel', ['reason' => 'مشتری سفارش را لغو کرد'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(5, $this->product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'order_cancelled')->count());
    }

    public function test_order_rejects_quantity_greater_than_tracked_stock(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/api/orders', $this->orderPayload(6), [
                'Idempotency-Key' => 'order-no-stock-test-0001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertSame(5, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_payment_and_partial_refund_are_reflected_in_order_and_cash_ledger(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/admin/api/cash-shift/open', ['opening_cash' => 500000])->assertCreated();
        $created = $this->postJson('/admin/api/orders', $this->orderPayload(1), [
            'Idempotency-Key' => 'order-payment-test-0001',
        ])->assertCreated();
        $orderId = $created->json('data.id');
        $total = (int) $created->json('data.total');

        $payment = $this->postJson('/admin/api/orders/'.$orderId.'/payments', [
            'amount' => $total,
            'method' => 'cash',
        ], ['Idempotency-Key' => 'payment-test-key-0001'])->assertCreated();
        $paymentId = $payment->json('data.id');

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'payment_status' => 'paid']);
        $this->assertSame(1, CashMovement::query()->where('direction', 'in')->count());

        $this->postJson('/admin/api/payments/'.$paymentId.'/refunds', [
            'amount' => 25000,
            'reason' => 'اصلاح سفارش طبق درخواست مشتری',
        ], ['Idempotency-Key' => 'refund-test-key-0001'])->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'payment_status' => 'partially_refunded']);
        $this->assertSame(1, CashMovement::query()->where('direction', 'out')->count());
        $this->assertDatabaseHas('cash_shifts', ['status' => 'open', 'open_lock' => 1]);
    }

    public function test_inventory_adjustment_is_idempotent_and_cannot_make_stock_negative(): void
    {
        $this->actingAs($this->admin);
        $payload = [
            'product_id' => $this->product->id,
            'direction' => 'in',
            'quantity' => 3,
            'reason' => 'رسید خرید تست',
        ];
        $headers = ['Idempotency-Key' => 'inventory-test-key-0001'];

        $first = $this->postJson('/admin/api/inventory/adjustments', $payload, $headers)->assertCreated();
        $second = $this->postJson('/admin/api/inventory/adjustments', $payload, $headers)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(8, $this->product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'manual')->count());

        $this->postJson('/admin/api/inventory/adjustments', [
            'product_id' => $this->product->id,
            'direction' => 'out',
            'quantity' => 100,
            'reason' => 'اصلاح تست بیش از موجودی',
        ], ['Idempotency-Key' => 'inventory-test-key-0002'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->assertSame(8, $this->product->fresh()->stock_quantity);
    }

    public function test_cash_payment_requires_open_shift(): void
    {
        $this->actingAs($this->admin);
        $created = $this->postJson('/admin/api/orders', $this->orderPayload(1), [
            'Idempotency-Key' => 'order-no-shift-test-0001',
        ])->assertCreated();

        $this->postJson('/admin/api/orders/'.$created->json('data.id').'/payments', [
            'amount' => (int) $created->json('data.total'),
            'method' => 'cash',
        ], ['Idempotency-Key' => 'payment-no-shift-test-0001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cash_shift']);
    }

    public function test_settings_and_report_endpoints_are_authenticated_and_responsive(): void
    {
        $this->actingAs($this->admin);

        $this->getJson('/admin/api/settings')->assertOk()->assertJsonPath('data.currency_label', 'تومان');
        $this->patchJson('/admin/api/settings', ['tax_basis_points' => 900])->assertOk()
            ->assertJsonPath('data.tax_basis_points', 900);

        $this->getJson('/admin/api/reports/sales')
            ->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'daily', 'payments_by_method', 'top_products']]);
    }

    public function test_qr_menu_displays_active_food_options_images_and_price_rules(): void
    {
        $table = RestaurantTable::query()->create([
            'name' => 'میز منوی تست',
            'token' => str_repeat('d', 64),
            'is_active' => true,
        ]);
        Product::query()->whereKey($this->product->id)->update([
            'description' => 'قهوه تست با توضیحات کامل',
            'ingredients' => 'قهوه، شیر',
            'allergens' => json_encode(['شیر'], JSON_UNESCAPED_UNICODE),
            'dietary_tags' => json_encode(['گیاهی نیست'], JSON_UNESCAPED_UNICODE),
            'calories' => 180,
            'preparation_time_minutes' => 7,
        ]);
        ProductOption::query()->create([
            'product_id' => $this->product->id,
            'name' => 'سایز بزرگ',
            'type' => 'size',
            'price' => 25000,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ProductOption::query()->create([
            'product_id' => $this->product->id,
            'name' => 'سیروپ',
            'type' => 'addon',
            'price' => 10000,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $settings = RestaurantSetting::current();
        $settings->forceFill([
            'name' => 'کافه تست',
            'currency_label' => 'تومان',
            'tax_basis_points' => 900,
            'service_basis_points' => 500,
            'customer_menu_enabled' => true,
        ])->save();

        $this->get('/menu/'.$table->token)->assertOk()->assertSee('میز منوی تست');
        $this->getJson('/menu/'.$table->token.'/data')
            ->assertOk()
            ->assertJsonPath('data.table.name', 'میز منوی تست')
            ->assertJsonPath('data.settings.name', 'کافه تست')
            ->assertJsonPath('data.settings.tax_basis_points', 900)
            ->assertJsonPath('data.settings.service_basis_points', 500)
            ->assertJsonPath('data.categories.0.products.0.name', 'محصول تست')
            ->assertJsonPath('data.categories.0.products.0.base_price', 125000)
            ->assertJsonPath('data.categories.0.products.0.description', 'قهوه تست با توضیحات کامل')
            ->assertJsonPath('data.categories.0.products.0.ingredients', 'قهوه، شیر')
            ->assertJsonPath('data.categories.0.products.0.allergens.0', 'شیر')
            ->assertJsonPath('data.categories.0.products.0.dietary_tags.0', 'گیاهی نیست')
            ->assertJsonPath('data.categories.0.products.0.calories', 180)
            ->assertJsonPath('data.categories.0.products.0.preparation_time_minutes', 7)
            ->assertJsonPath('data.categories.0.products.0.options.0.name', 'سایز بزرگ')
            ->assertJsonPath('data.categories.0.products.0.options.0.price', 25000)
            ->assertJsonPath('data.categories.0.products.0.options.1.name', 'سیروپ')
            ->assertJsonPath('data.categories.0.products.0.options.1.price', 10000);
    }

    public function test_customer_qr_cannot_submit_an_order_for_another_table(): void
    {
        $table = RestaurantTable::query()->create([
            'name' => 'میز QR تست',
            'token' => str_repeat('a', 64),
            'is_active' => true,
        ]);
        $otherTable = RestaurantTable::query()->create([
            'name' => 'میز دیگر تست',
            'token' => str_repeat('b', 64),
            'is_active' => true,
        ]);
        RestaurantSetting::current();

        $this->postJson('/menu/'.$table->token.'/orders', [
            'restaurant_table_id' => $otherTable->id,
            ...$this->orderPayload(1),
            'channel' => 'dine_in',
        ], ['Idempotency-Key' => 'qr-table-mismatch-0001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['restaurant_table_id']);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(5, $this->product->fresh()->stock_quantity);
    }

    public function test_order_state_transitions_and_print_endpoints_follow_the_saved_order(): void
    {
        $this->actingAs($this->admin);
        $created = $this->postJson('/admin/api/orders', $this->orderPayload(1), [
            'Idempotency-Key' => 'order-status-test-0001',
        ])->assertCreated();
        $orderId = $created->json('data.id');

        $this->patchJson('/admin/api/orders/'.$orderId.'/status', ['status' => 'delivered'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        foreach (['confirmed', 'preparing', 'ready', 'delivered'] as $status) {
            $this->patchJson('/admin/api/orders/'.$orderId.'/status', ['status' => $status])->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $this->getJson('/admin/api/orders/'.$orderId.'/invoice')->assertOk()->assertJsonPath('type', 'invoice');
        $this->getJson('/admin/api/orders/'.$orderId.'/kitchen-slip')->assertOk()->assertJsonPath('type', 'kitchen');
        $this->getJson('/admin/api/orders/'.$orderId.'/receipt')->assertUnprocessable();

        $firstPrint = $this->postJson('/admin/api/orders/'.$orderId.'/print-log', ['type' => 'invoice'])
            ->assertCreated()->assertJsonPath('data.is_reprint', false);
        $secondPrint = $this->postJson('/admin/api/orders/'.$orderId.'/print-log', ['type' => 'invoice'])
            ->assertCreated()->assertJsonPath('data.is_reprint', true);
        $this->assertSame($firstPrint->json('data.document_number'), $secondPrint->json('data.document_number'));
    }

    public function test_reservation_creation_is_idempotent_and_prevents_table_overlap(): void
    {
        $this->actingAs($this->admin);
        $table = RestaurantTable::query()->create([
            'name' => 'میز رزرو تست',
            'token' => str_repeat('c', 64),
            'is_active' => true,
        ]);
        $startsAt = now()->addDay()->startOfHour()->format('Y-m-d H:i:s');
        $payload = [
            'restaurant_table_id' => $table->id,
            'customer_name' => 'مشتری تست',
            'phone' => '09120000000',
            'party_size' => 3,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'notes' => 'تست رزرو',
        ];

        $first = $this->postJson('/admin/api/reservations', $payload, [
            'Idempotency-Key' => 'reservation-test-key-01',
        ])->assertCreated();
        $retry = $this->postJson('/admin/api/reservations', $payload, [
            'Idempotency-Key' => 'reservation-test-key-01',
        ])->assertOk();

        $this->assertSame($first->json('data.id'), $retry->json('data.id'));
        $this->assertSame(1, \App\Models\Reservation::query()->count());

        $conflicting = $payload;
        $conflicting['starts_at'] = now()->parse($startsAt)->addMinutes(30)->format('Y-m-d H:i:s');
        $this->postJson('/admin/api/reservations', $conflicting, [
            'Idempotency-Key' => 'reservation-test-key-02',
        ])->assertUnprocessable()->assertJsonValidationErrors(['restaurant_table_id']);

        $this->assertSame(1, \App\Models\Reservation::query()->count());
    }

    public function test_cash_shift_close_reconciles_opening_balance_and_movements(): void
    {
        $this->actingAs($this->admin);
        $shift = $this->postJson('/admin/api/cash-shift/open', ['opening_cash' => 1000])->assertCreated();
        $shiftId = $shift->json('data.id');

        $this->postJson('/admin/api/cash-shift/'.$shiftId.'/movements', [
            'direction' => 'in', 'amount' => 500, 'reason' => 'شارژ صندوق',
        ], ['Idempotency-Key' => 'cash-movement-test-0001'])->assertCreated();
        $this->postJson('/admin/api/cash-shift/'.$shiftId.'/movements', [
            'direction' => 'out', 'amount' => 100, 'reason' => 'تنخواه تست',
        ], ['Idempotency-Key' => 'cash-movement-test-0002'])->assertCreated();

        $this->postJson('/admin/api/cash-shift/'.$shiftId.'/close', [
            'counted_cash' => 1400, 'note' => 'تست تطبیق صندوق',
        ])->assertOk()
          ->assertJsonPath('data.expected_cash', 1400)
          ->assertJsonPath('data.counted_cash', 1400)
          ->assertJsonPath('data.difference', 0)
          ->assertJsonPath('data.status', 'closed');
    }

    private function orderPayload(int $quantity): array
    {
        return [
            'channel' => 'counter',
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'options' => [],
            ]],
            'discount' => 0,
        ];
    }

    public function test_public_reservation_requires_a_table_before_confirmation(): void
    {
        $startsAt = \Illuminate\Support\Carbon::now(config('app.timezone'))->addDays(5)->setTime(17, 0);
        $reservation = \App\Models\Reservation::query()->create([
            'reference' => 'RSV-PUBLIC-001',
            'customer_name' => 'مشتری رزرو',
            'phone' => '+989121234567',
            'party_size' => 2,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'pending',
            'source' => 'public',
            'idempotency_key' => 'public-transition-test-01',
            'idempotency_hash' => hash('sha256', 'reservation-1'),
        ]);

        $this->actingAs($this->admin)
            ->patchJson('/admin/api/reservations/'.$reservation->id.'/status', ['status' => 'confirmed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('restaurant_table_id');

        $this->assertSame('pending', $reservation->fresh()->status->value);
    }

    public function test_public_reservation_can_be_confirmed_after_assigning_an_active_table(): void
    {
        $table = RestaurantTable::query()->create([
            'name' => 'میز رزرو تست',
            'token' => \Illuminate\Support\Str::random(48),
            'is_active' => true,
        ]);
        $startsAt = \Illuminate\Support\Carbon::now(config('app.timezone'))->addDays(6)->setTime(18, 0);
        $reservation = \App\Models\Reservation::query()->create([
            'reference' => 'RSV-PUBLIC-002',
            'customer_name' => 'مشتری رزرو دوم',
            'phone' => '+989121234568',
            'party_size' => 3,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'pending',
            'source' => 'public',
            'idempotency_key' => 'public-transition-test-02',
            'idempotency_hash' => hash('sha256', 'reservation-2'),
        ]);

        $this->actingAs($this->admin)
            ->patchJson('/admin/api/reservations/'.$reservation->id.'/status', [
                'status' => 'confirmed',
                'restaurant_table_id' => $table->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.table.id', $table->id);

        $this->assertSame($table->id, $reservation->fresh()->restaurant_table_id);
    }

    public function test_reservation_confirmation_rejects_overlapping_table_windows(): void
    {
        $table = RestaurantTable::query()->create([
            'name' => 'میز هم‌پوشانی تست',
            'token' => \Illuminate\Support\Str::random(48),
            'is_active' => true,
        ]);
        $startsAt = \Illuminate\Support\Carbon::now(config('app.timezone'))->addDays(7)->setTime(18, 0);
        \App\Models\Reservation::query()->create([
            'reference' => 'RSV-PUBLIC-003',
            'restaurant_table_id' => $table->id,
            'customer_name' => 'رزرو موجود',
            'phone' => '+989121234569',
            'party_size' => 2,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'confirmed',
            'source' => 'public',
            'idempotency_key' => 'public-transition-test-03',
            'idempotency_hash' => hash('sha256', 'reservation-3'),
        ]);
        $pending = \App\Models\Reservation::query()->create([
            'reference' => 'RSV-PUBLIC-004',
            'customer_name' => 'رزرو تداخل',
            'phone' => '+989121234570',
            'party_size' => 2,
            'starts_at' => $startsAt->copy()->addMinutes(30),
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(120),
            'status' => 'pending',
            'source' => 'public',
            'idempotency_key' => 'public-transition-test-04',
            'idempotency_hash' => hash('sha256', 'reservation-4'),
        ]);

        $this->actingAs($this->admin)
            ->patchJson('/admin/api/reservations/'.$pending->id.'/status', [
                'status' => 'confirmed',
                'restaurant_table_id' => $table->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('restaurant_table_id');

        $this->assertSame('pending', $pending->fresh()->status->value);
    }


    public function test_foundation_seeder_does_not_create_demo_catalogue_or_fake_transactions(): void
    {
        config([
            'restaurant.name' => 'کافه واقعی تست',
            'restaurant.phone' => '02112345678',
            'restaurant.address' => 'نشانی ثبت‌شده',
            'restaurant.currency_label' => 'تومان',
            'restaurant.initial_table_names' => [],
        ]);
        $categoriesBefore = Category::query()->count();
        $productsBefore = Product::query()->count();

        Artisan::call('db:seed', ['--class' => \Database\Seeders\RestaurantFoundationSeeder::class]);

        $this->assertDatabaseHas('restaurant_settings', [
            'singleton_key' => 'main',
            'name' => 'کافه واقعی تست',
            'phone' => '02112345678',
        ]);
        $this->assertSame($categoriesBefore, Category::query()->count());
        $this->assertSame($productsBefore, Product::query()->count());
        $this->assertDatabaseCount('restaurant_tables', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_foundation_seeder_creates_only_explicit_table_names_and_is_idempotent(): void
    {
        config([
            'restaurant.name' => 'کافه میزهای تست',
            'restaurant.initial_table_names' => ['میز سالن', 'میز تراس', 'میز سالن'],
        ]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\RestaurantFoundationSeeder::class]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\RestaurantFoundationSeeder::class]);

        $this->assertDatabaseCount('restaurant_tables', 2);
        $this->assertDatabaseHas('restaurant_tables', ['name' => 'میز سالن', 'is_active' => true]);
        $this->assertDatabaseHas('restaurant_tables', ['name' => 'میز تراس', 'is_active' => true]);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_edit_and_delete_catalog_records_when_they_have_no_history(): void
    {
        $this->actingAs($this->admin);

        $this->patchJson('/admin/api/categories/'.$this->category->id, [
            'name' => 'دسته ویرایش‌شده',
            'sort_order' => 4,
            'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'دسته ویرایش‌شده');

        $this->deleteJson('/admin/api/products/'.$this->product->id)->assertOk();
        $this->deleteJson('/admin/api/categories/'.$this->category->id)->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $this->product->id]);
        $this->assertDatabaseMissing('categories', ['id' => $this->category->id]);
    }

    public function test_environment_seeded_table_edits_survive_repeated_seed_runs(): void
    {
        config(['restaurant.initial_table_names' => ['میز اولیه']]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\RestaurantFoundationSeeder::class]);

        $table = RestaurantTable::query()->where('name', 'میز اولیه')->firstOrFail();
        $table->forceFill([
            'name' => 'میز ویرایش‌شده',
            'description' => 'بخش تراس',
            'is_active' => false,
        ])->save();

        Artisan::call('db:seed', ['--class' => \Database\Seeders\RestaurantFoundationSeeder::class]);

        $this->assertSame(1, RestaurantTable::query()->count());
        $this->assertDatabaseHas('restaurant_tables', [
            'id' => $table->id,
            'name' => 'میز ویرایش‌شده',
            'description' => 'بخش تراس',
            'is_active' => 0,
            'seed_key' => 'env-table:'.hash('sha256', 'میز اولیه'),
        ]);
        $this->assertDatabaseMissing('restaurant_tables', ['name' => 'میز اولیه']);
    }

}
