<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderItemController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('locale', [LocaleController::class, 'update'])->name('locale');
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware(['auth', 'admin'])->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
            Route::resource('products', ProductController::class)->except(['show']);
            Route::post('orders/bulk', [OrderController::class, 'bulk'])->name('orders.bulk');
            Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
            Route::post('promo-codes/bulk', [PromoCodeController::class, 'bulk'])->name('promo-codes.bulk');
            Route::resource('promo-codes', PromoCodeController::class)
                ->parameters(['promo-codes' => 'promoCode'])
                ->except(['show']);
            Route::get('order-items/{orderItem}/edit', [OrderItemController::class, 'edit'])->name('order-items.edit');
            Route::put('order-items/{orderItem}', [OrderItemController::class, 'update'])->name('order-items.update');
            Route::delete('order-items/{orderItem}', [OrderItemController::class, 'destroy'])->name('order-items.destroy');
        });
    });
