<?php

use Illuminate\Support\Facades\Route;

/*
| Area Customer / Toko  —  PIC: Muhammad Arif Bagus (feature/storefront-checkout)
| Nama route shop.*. Controller taruh di app/Http/Controllers/Shop.
| Route::view di bawah hanya placeholder, ganti dengan controller.
*/

Route::name('shop.')->group(function () {
    // Publik
    Route::view('/', 'shop.home')->name('home');
    Route::view('/products/{product}', 'shop.products.show')->name('products.show');

    // Wajib login sebagai customer
    Route::middleware(['auth', 'role:customer'])->group(function () {
        Route::view('/checkout/{product}', 'shop.checkout')->name('checkout');
        Route::view('/orders', 'shop.orders.index')->name('orders.index');
    });
});
