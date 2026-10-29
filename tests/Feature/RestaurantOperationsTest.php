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
            ->assertSee('طعم خوب')
            ->assertSee('لاته تست')
            ->assertSee('ورود کارکنان');

        $this->actingAs($this->admin)->get('/')
            ->assertOk()
            ->assertSee('پنل مدیریت')
            ->assertSee('خروج')
            ->assertSee(route('admin.logout'), false);

        $this->get('/menu')->assertOk()->assertSee('منو برای')->assertSee('لاته تست');
    }

    public function test_local_seeder_accepts_development_password_only_in_local_environment(): void
    {
        config([
            'app.env' => 'local',
            'admin.name' => 'Local Demo Admin',
            'admin.email' => 'local-demo@example.test',
            'admin.password' => 'password',
        ]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdminUserSeeder::class]);

        $this->post('/admin/login', [
            'email' => 'local-demo@example.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs(User::query()->where('email', 'local-demo@example.test')->first());
    }

    public function test_admin_login_works_with_seeded_env_configuration(): void
    {
        config([
            'admin.name' => 'Seeded Test Admin',
            'admin.email' => 'seeded-admin@example.test',
            'admin.password' => 'seeded-test-password-12345',
        ]);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdminUserSeeder::class]);

        $this->post('/admin/login', [
            'email' => 'seeded-admin@example.test',
            'password' => 'seeded-test-password-12345',
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

    public function test_recipe_ingredients_are_consumed_atomically_and_restored_when_an_order_is_cancelled(): void
    {
        $this->actingAs($this->admin);
        $ingredient = \App\Models\Ingredient::query()->create([
            'name' => 'دانه قهوه تست',
            'unit' => 'g',
            'stock_quantity' => '100.000',
            'low_stock_threshold' => '10.000',
            'cost_per_unit' => 25,
            'is_active' => true,
        ]);

        $this->putJson('/admin/api/products/'.$this->product->id.'/recipe', [
            'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => 18]],
        ])->assertOk()
          ->assertJsonPath('data.recipe_ingredients.0.id', $ingredient->id);

        $created = $this->postJson('/admin/api/orders', $this->orderPayload(2), [
            'Idempotency-Key' => 'recipe-consumption-order-0001',
        ])->assertCreated();

        $this->assertSame('64.000', $ingredient->fresh()->stock_quantity);
        $orderId = $created->json('data.id');
        $this->assertSame(18.0, (float) Order::query()->findOrFail($orderId)->items()->first()->recipe_snapshot[0]['quantity']);

        $this->postJson('/admin/api/orders/'.$orderId.'/cancel', ['reason' => 'لغو سفارش تست'])
            ->assertOk();

        $this->assertSame('100.000', $ingredient->fresh()->stock_quantity);
        $this->assertSame(5, $this->product->fresh()->stock_quantity);
        $this->assertDatabaseHas('ingredient_inventory_movements', [
            'ingredient_id' => $ingredient->id,
            'order_id' => $orderId,
            'movement_type' => 'order_cancelled',
            'stock_after' => '100.000',
        ]);
    }

    public function test_public_takeaway_order_is_unpaid_and_saved_in_the_main_order_system(): void
    {
        $this->postJson('/online-orders', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'customer_name' => 'مشتری سایت',
            'customer_phone' => '09120000000',
            'customer_note' => 'برای تحویل حضوری',
            'idempotency_key' => 'public-website-order-0001',
        ])->assertCreated()
          ->assertJsonPath('data.status', 'new')
          ->assertJsonPath('data.payment_status', 'unpaid');

        $order = Order::query()->where('customer_phone', '09120000000')->firstOrFail();
        $this->assertSame('website', $order->source);
        $this->assertSame('takeaway', $order->channel);
        $this->assertSame(125000, $order->subtotal);
        $this->assertSame(4, $this->product->fresh()->stock_quantity);
    }

    public function test_public_birthday_request_is_recorded_as_pending_for_staff_confirmation(): void
    {
        $this->post('/reserve', [
            'customer_name' => 'مشتری جشن',
            'phone' => '09121111111',
            'email' => 'birthday@example.test',
            'purpose' => 'birthday',
            'occasion_name' => 'تولد تست',
            'party_size' => 8,
            'starts_at' => now()->addDays(4)->format('Y-m-d\TH:i'),
            'duration_minutes' => 120,
            'estimated_budget' => 5000000,
            'notes' => 'کیک و تزیین ساده',
            'idempotency_key' => 'public-birthday-request-0001',
            'website' => '',
        ])->assertRedirect(route('public.reservations.received'));

        $reservation = \App\Models\Reservation::query()->where('phone', '09121111111')->firstOrFail();
        $this->assertSame('website', $reservation->source);
        $this->assertSame('birthday', $reservation->purpose);
        $this->assertSame('pending', $reservation->status->value);
        $this->assertSame('تولد تست', $reservation->occasion_name);
        $this->assertNull($reservation->created_by);
    }

    public function test_signed_marketplace_webhook_creates_one_order_for_replayed_external_order(): void
    {
        config(['integrations.order_webhook_secret' => str_repeat('X', 40)]);
        $body = json_encode([
            'external_order_id' => 'marketplace-order-42',
            'customer_name' => 'مشتری بیرونی',
            'customer_phone' => '09123333333',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, str_repeat('X', 40));
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_GIL_SIGNATURE' => $signature,
        ];

        $this->call('POST', '/api/integrations/orders/snappfood', [], [], [], $server, $body)
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'unpaid');

        $this->call('POST', '/api/integrations/orders/snappfood', [], [], [], $server, $body)
            ->assertOk();

        $this->assertSame(1, Order::query()->count());
        $order = Order::query()->firstOrFail();
        $this->assertSame('snappfood', $order->source);
        $this->assertSame('snappfood', $order->external_provider);
        $this->assertSame('marketplace-order-42', $order->external_order_id);
        $this->assertSame('unpaid', $order->payment_status->value);
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
}
