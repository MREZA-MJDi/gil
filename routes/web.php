<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CashShiftController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RestaurantTableController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Customer\MenuController as CustomerMenuController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
});
Route::post('/admin/logout', [AuthController::class, 'destroy'])->middleware(['auth', 'can:access-admin'])->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'transition'])->name('orders.transition');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('/orders/{order}/payments', [PaymentController::class, 'index'])->name('orders.payments.index');
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
        Route::post('/payments/{payment}/refunds', [PaymentController::class, 'refund'])->name('payments.refunds.store');

        Route::get('/orders/{order}/invoice', [PrintController::class, 'invoice'])->name('orders.invoice');
        Route::get('/orders/{order}/receipt', [PrintController::class, 'receipt'])->name('orders.receipt');
        Route::get('/orders/{order}/kitchen-slip', [PrintController::class, 'kitchen'])->name('orders.kitchen');
        Route::post('/orders/{order}/print-log', [PrintController::class, 'log'])->name('orders.print-log');

        Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::post('/inventory/adjustments', [InventoryController::class, 'adjust'])->name('inventory.adjustments.store');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/status', [ProductController::class, 'status'])->name('products.status');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/tables', [RestaurantTableController::class, 'index'])->name('tables.index');
        Route::post('/tables', [RestaurantTableController::class, 'store'])->name('tables.store');
        Route::post('/tables/{table}/rotate-qr', [RestaurantTableController::class, 'rotateToken'])->name('tables.rotate-qr');

        Route::get('/cash-shift', [CashShiftController::class, 'current'])->name('cash-shift.current');
        Route::post('/cash-shift/open', [CashShiftController::class, 'open'])->name('cash-shift.open');
        Route::post('/cash-shift/{cashShift}/close', [CashShiftController::class, 'close'])->name('cash-shift.close');
        Route::post('/cash-shift/{cashShift}/movements', [CashShiftController::class, 'movement'])->name('cash-shift.movements.store');

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'transition'])->name('reservations.transition');
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
        Route::patch('/feedback/{feedback}/response', [FeedbackController::class, 'respond'])->name('feedback.respond');

        Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

// QR tokens are random capabilities scoped to a single table's menu.
Route::prefix('menu/{token}')->name('customer.menu.')->middleware('throttle:60,1')->group(function () {
    Route::get('/', [CustomerMenuController::class, 'show'])->name('show');
    Route::get('/data', [CustomerMenuController::class, 'data'])->name('data');
    Route::post('/orders', [CustomerMenuController::class, 'placeOrder'])->middleware('throttle:10,1')->name('orders.store');
});
