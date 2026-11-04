<?php

use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentAccountController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromoMaterialController;
use App\Http\Controllers\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
| Area Admin  —  PIC: Muhammad Hanif Hawari (feature/admin-dashboard)
| Prefix URL /admin, nama route admin.*, middleware auth + role:admin
| (diatur di bootstrap/app.php). Controller taruh di app/Http/Controllers/Admin.
*/

Route::get('/', DashboardController::class)->name('dashboard');

// Verifikasi pembayaran
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}/proof', [OrderController::class, 'proof'])->name('orders.proof');
Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');

// Produk & komisi
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');
Route::put('/products/{product:id}', [ProductController::class, 'update'])->name('products.update');
Route::patch('/products/{product:id}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
Route::delete('/products/{product:id}', [ProductController::class, 'destroy'])->name('products.destroy');
Route::get('/products/{product:id}/file', [ProductController::class, 'download'])->name('products.download');

// Materi promosi per produk
Route::get('/products/{product:id}/promo-materials', [PromoMaterialController::class, 'index'])->name('products.promo-materials.index');
Route::post('/products/{product:id}/promo-materials', [PromoMaterialController::class, 'store'])->name('products.promo-materials.store');
Route::delete('/promo-materials/{promoMaterial}', [PromoMaterialController::class, 'destroy'])->name('promo-materials.destroy');

// Pencairan komisi afiliator
Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
Route::get('/withdrawals/{withdrawal}/proof', [WithdrawalController::class, 'proof'])->name('withdrawals.proof');
Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
Route::post('/withdrawals/{withdrawal}/pay', [WithdrawalController::class, 'pay'])->name('withdrawals.pay');
Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

// QRIS & rekening tujuan pembayaran customer
Route::get('/payment-accounts', [PaymentAccountController::class, 'index'])->name('payment-accounts.index');
Route::post('/payment-accounts', [PaymentAccountController::class, 'store'])->name('payment-accounts.store');
Route::put('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'update'])->name('payment-accounts.update');
Route::patch('/payment-accounts/{paymentAccount}/toggle', [PaymentAccountController::class, 'toggle'])->name('payment-accounts.toggle');
Route::delete('/payment-accounts/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('payment-accounts.destroy');

// Afiliator
Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliates.index');
Route::get('/affiliates/{affiliate}', [AffiliateController::class, 'show'])->name('affiliates.show');
Route::patch('/affiliates/{affiliate}/toggle', [AffiliateController::class, 'toggle'])->name('affiliates.toggle');

// Belum dikerjakan (placeholder)
Route::view('/reports', 'admin.reports.index')->name('reports.index');
