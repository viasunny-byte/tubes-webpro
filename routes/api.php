<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CheckoutController;

/*
|--------------------------------------------------------------------------
| API ROUTES
|--------------------------------------------------------------------------
*/

// AUTH
Route::post('/login', [AuthController::class, 'login']);

// SHOP (PUBLIC)
Route::get('/products', [ShopController::class, 'index']);
Route::get('/products/{slug}', [ShopController::class, 'show']);

// CART (GUEST BOLEH)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/add', [CartController::class, 'add']);
Route::put('/cart/increase/{rowId}', [CartController::class, 'increase']);
Route::put('/cart/decrease/{rowId}', [CartController::class, 'decrease']);
Route::delete('/cart/{rowId}', [CartController::class, 'remove']);
Route::delete('/cart', [CartController::class, 'clear']);

// WISHLIST (GUEST BOLEH)
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/add', [WishlistController::class, 'add']);
Route::delete('/wishlist/{rowId}', [WishlistController::class, 'remove']);
Route::delete('/wishlist', [WishlistController::class, 'clear']);
Route::post('/wishlist/move-to-cart/{rowId}', [WishlistController::class, 'moveToCart']);

// LOGIN REQUIRED
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [UserController::class, 'profile']);

    // CHECKOUT
    Route::get('/checkout/summary', [CheckoutController::class, 'summary']);
    Route::post('/checkout', [CheckoutController::class, 'placeOrder']);
});