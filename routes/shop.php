<?php

use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\ProductController;
use Illuminate\Support\Facades\Route;

/*
| Area Customer / Toko  —  PIC: Muhammad Arif Bagus (feature/storefront-checkout)
| Nama route shop.*. Controller taruh di app/Http/Controllers/Shop.
| Route::view di bawah hanya placeholder, ganti dengan controller.
*/

Route::name('shop.')->group(function () {
    // Publik
    Route::get('/', [ProductController::class, 'index'])->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

    // Wajib login sebagai customer
    Route::middleware(['auth', 'role:customer'])->group(function () {
        Route::get('/checkout/{product:slug}', [CheckoutController::class, 'create'])->name('checkout');
        Route::post('/checkout/{product:slug}', [CheckoutController::class, 'store'])->name('checkout.store');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:invoice_number}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order:invoice_number}/pay', [OrderController::class, 'pay'])->name('orders.pay');
        Route::post('/orders/{order:invoice_number}/proof', [OrderController::class, 'uploadProof'])->name('orders.proof');
        Route::get('/orders/{order:invoice_number}/download', [OrderController::class, 'download'])
            ->middleware('signed')
            ->name('orders.download');
    });
});
