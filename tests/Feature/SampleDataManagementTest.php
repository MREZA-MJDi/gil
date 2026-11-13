<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Expense;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleDataManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Sample Management Admin',
            'email' => 'sample-management@example.test',
            'password' => 'sample-management-password-123',
            'is_admin' => true,
        ]);

        $this->actingAs($this->admin);
    }

    private function openShift(): CashShift
    {
        $this->postJson('/admin/api/cash-shift/open', ['opening_cash' => 500000])->assertCreated();
        return CashShift::query()->where('status', 'open')->firstOrFail();
    }

    public function test_cash_expense_edit_keeps_the_open_shift_ledger_in_sync(): void
    {
        $this->openShift();

        $created = $this->postJson('/admin/api/expenses', [
            'title' => 'DEMO · خرید اولیه',
            'category' => 'ingredients',
            'amount' => 120000,
            'payment_method' => 'cash',
            'is_paid' => true,
            'note' => 'یادداشت نمونه',
        ])->assertCreated();

        $expenseId = $created->json('data.id');

        $this->patchJson('/admin/api/expenses/'.$expenseId, [
            'title' => 'DEMO · خرید ویرایش‌شده',
            'category' => 'packaging',
            'amount' => 85000,
            'payment_method' => 'cash',
            'is_paid' => true,
            'note' => 'اصلاح مبلغ',
        ])->assertOk()
            ->assertJsonPath('data.title', 'DEMO · خرید ویرایش‌شده')
            ->assertJsonPath('data.amount', 85000);

        $this->assertDatabaseHas('cash_movements', [
            'expense_id' => $expenseId,
            'amount' => 85000,
            'reason' => 'DEMO · خرید ویرایش‌شده',
        ]);
    }

    public function test_only_demo_expenses_can_be_deleted_and_the_open_cash_movement_is_removed_with_them(): void
    {
        $this->openShift();

        $demo = Expense::query()->create([
            'cash_shift_id' => CashShift::query()->where('status', 'open')->value('id'),
            'user_id' => $this->admin->id,
            'title' => 'هزینه‌ای که بعداً تغییر نام می‌گیرد',
            'seed_key' => 'demo-expense:test-removable',
            'category' => 'ingredients',
            'amount' => 45000,
            'payment_method' => 'cash',
            'is_paid' => true,
            'paid_at' => now(),
        ]);
        CashMovement::query()->create([
            'cash_shift_id' => $demo->cash_shift_id,
            'user_id' => $this->admin->id,
            'expense_id' => $demo->id,
            'direction' => 'out',
            'type' => 'expense',
            'amount' => $demo->amount,
            'reason' => $demo->title,
            'idempotency_key' => 'expense:'.$demo->id,
            'occurred_at' => now(),
        ]);

        $this->patchJson('/admin/api/expenses/'.$demo->id, [
            'title' => 'هزینه نمونه با عنوان جدید',
            'category' => 'ingredients',
            'amount' => 45000,
            'payment_method' => 'cash',
            'is_paid' => true,
            'note' => 'شناسه نمونه باید مستقل از عنوان باقی بماند.',
        ])->assertOk();

        $this->deleteJson('/admin/api/expenses/'.$demo->id)->assertOk();
        $this->assertDatabaseMissing('expenses', ['id' => $demo->id]);
        $this->assertDatabaseMissing('cash_movements', ['expense_id' => $demo->id]);

        $real = Expense::query()->create([
            'user_id' => $this->admin->id,
            'title' => 'DEMO · فقط عنوان شبیه نمونه است',
            'category' => 'rent',
            'amount' => 200000,
            'payment_method' => 'other',
            'is_paid' => false,
        ]);
        $this->deleteJson('/admin/api/expenses/'.$real->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expense');
        $this->assertDatabaseHas('expenses', ['id' => $real->id]);
    }

    public function test_sample_reservations_can_be_edited_and_deleted_from_the_dashboard(): void
    {
        $startsAt = now()->addDays(3)->setTime(18, 0);
        $reservation = Reservation::query()->create([
            'reference' => 'DEMO-RSV-MANAGEMENT',
            'customer_name' => 'مشتری نمونه',
            'phone' => '09120000000',
            'party_size' => 4,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'pending',
            'source' => 'staff',
            'purpose' => 'table',
            'notes' => 'رزرو نمونه',
            'created_by' => $this->admin->id,
        ]);

        $this->patchJson('/admin/api/reservations/'.$reservation->id, [
            'restaurant_table_id' => null,
            'customer_name' => 'مشتری ویرایش‌شده',
            'phone' => '09121111111',
            'email' => 'guest@example.test',
            'party_size' => 6,
            'starts_at' => $startsAt->copy()->addDay()->toDateTimeString(),
            'duration_minutes' => 120,
            'purpose' => 'birthday',
            'occasion_name' => 'تولد',
            'estimated_budget' => 3000000,
            'notes' => 'یادداشت ویرایش‌شده',
        ])->assertOk()
            ->assertJsonPath('data.customer_name', 'مشتری ویرایش‌شده')
            ->assertJsonPath('data.party_size', 6)
            ->assertJsonPath('data.purpose', 'birthday');

        $this->deleteJson('/admin/api/reservations/'.$reservation->id)->assertOk();
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_real_reservations_cannot_be_deleted_through_the_sample_delete_endpoint(): void
    {
        $startsAt = now()->addDays(2);
        $reservation = Reservation::query()->create([
            'reference' => 'RSV-REAL-123456',
            'customer_name' => 'مهمان واقعی',
            'phone' => '09129999999',
            'party_size' => 2,
            'starts_at' => $startsAt,
            'duration_minutes' => 90,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'pending',
            'source' => 'staff',
            'purpose' => 'table',
            'created_by' => $this->admin->id,
        ]);

        $this->deleteJson('/admin/api/reservations/'.$reservation->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reservation');
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }
}
