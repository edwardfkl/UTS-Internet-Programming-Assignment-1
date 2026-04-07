<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartAttachController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartSessionController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/cart/attach', [CartAttachController::class, 'store']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::post('/cart/sessions', [CartSessionController::class, 'store']);

Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'storeItem']);
Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem']);
