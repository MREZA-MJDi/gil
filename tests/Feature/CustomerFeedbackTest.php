<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\CustomerFeedback;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Feedback Test Admin',
            'email' => 'feedback-admin@example.test',
            'phone' => '+989121110001',
            'password' => 'test-only-password-12345',
            'is_admin' => true,
        ]);

        $this->customer = User::query()->create([
            'name' => 'مشتری آزمایش',
            'email' => 'feedback-customer@example.test',
            'phone' => '+989121234567',
            'password' => 'test-only-password-12345',
            'is_admin' => false,
        ]);

        $category = Category::query()->create([
            'name' => 'دسته بازخورد',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول بازخورد',
            'base_price' => 120000,
            'status' => 'active',
            'sort_order' => 1,
            'stock_quantity' => null,
        ]);
    }

    public function test_customer_can_submit_feedback_for_a_delivered_order_and_see_the_manager_reply(): void
    {
        $order = $this->createWebsiteOrderFor($this->customer);
        $order->forceFill(['status' => OrderStatus::Delivered])->save();

        $this->actingAs($this->customer)
            ->get('/account')
            ->assertOk()
            ->assertSee('ثبت بازخورد')
            ->assertSee($order->number)
            ->assertSee(route('customer.feedback.store'));

        $this->post(route('customer.feedback.store'), [
            'order_id' => $order->id,
            'kind' => 'praise',
            'rating' => 5,
            'message' => 'کیفیت سفارش عالی بود و بسته‌بندی هم مرتب بود.',
        ])->assertRedirect(route('customer.dashboard').'#customer-feedback');

        $feedback = CustomerFeedback::query()->sole();

        $this->assertDatabaseHas('customer_feedback', [
            'id' => $feedback->id,
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'kind' => 'praise',
            'rating' => 5,
            'status' => 'new',
        ]);

        $this->actingAs($this->admin)
            ->getJson('/admin/api/feedback')
            ->assertOk()
            ->assertJsonFragment(['message' => 'کیفیت سفارش عالی بود و بسته‌بندی هم مرتب بود.']);

        $this->patchJson('/admin/api/feedback/'.$feedback->id.'/response', [
            'status' => 'resolved',
            'admin_response' => 'از اینکه تجربه‌ات را با ما در میان گذاشتی ممنونیم.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->actingAs($this->customer)
            ->get('/account')
            ->assertOk()
            ->assertSee('بازخوردهای من')
            ->assertSee('پاسخ مجموعه')
            ->assertSee('از اینکه تجربه‌ات را با ما در میان گذاشتی ممنونیم.')
            ->assertSee('رسیدگی‌شده');
    }

    public function test_customer_cannot_review_another_persons_order_an_undelivered_order_or_review_twice(): void
    {
        $order = $this->createWebsiteOrderFor($this->customer);
        $payload = [
            'order_id' => $order->id,
            'kind' => 'suggestion',
            'rating' => 4,
            'message' => 'پیشنهاد می‌کنم گزینه‌های بیشتری اضافه شود.',
        ];

        $this->actingAs($this->customer)
            ->from(route('customer.dashboard'))
            ->post(route('customer.feedback.store'), $payload)
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHasErrors('order_id');

        $order->forceFill(['status' => OrderStatus::Delivered])->save();

        $otherCustomer = User::query()->create([
            'name' => 'مشتری دیگر',
            'email' => 'feedback-other@example.test',
            'phone' => '+989129998877',
            'password' => 'test-only-password-12345',
            'is_admin' => false,
        ]);

        $this->actingAs($otherCustomer)
            ->from(route('customer.dashboard'))
            ->post(route('customer.feedback.store'), $payload)
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHasErrors('order_id');

        $this->actingAs($this->customer)
            ->post(route('customer.feedback.store'), $payload)
            ->assertRedirect(route('customer.dashboard').'#customer-feedback');

        $this->from(route('customer.dashboard'))
            ->post(route('customer.feedback.store'), $payload)
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHasErrors('order_id');

        $this->assertSame(1, CustomerFeedback::query()->count());
    }

    private function createWebsiteOrderFor(User $customer): Order
    {
        $response = $this->postJson('/online-orders', [
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'options' => [],
            ]],
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'payment_preference' => 'at_counter',
        ], [
            'Idempotency-Key' => 'feedback-order-'.$customer->id.'-fixture-0001',
        ])->assertCreated();

        return Order::query()
            ->where('number', $response->json('data.number'))
            ->firstOrFail();
    }
}
