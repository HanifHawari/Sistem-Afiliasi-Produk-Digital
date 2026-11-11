<?php

use App\Http\Controllers\Affiliate\BankAccountController;
use App\Http\Controllers\Affiliate\DashboardController;
use App\Http\Controllers\Affiliate\LinkController;
use App\Http\Controllers\Affiliate\ProductController;
use App\Http\Controllers\Affiliate\PromoMaterialController;
use App\Http\Controllers\Affiliate\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
| Area Afiliator  —  PIC: Gibran Muhammad Ardan (feature/affiliate-dashboard)
| Prefix URL /affiliate, nama route affiliate.*, middleware auth + role:affiliate
| (diatur di bootstrap/app.php). Controller taruh di app/Http/Controllers/Affiliate.
|
| Catatan: registrasi afiliator (tamu, belum login) perlu route terpisah
| di luar grup ini, mis. di routes/auth.php.
*/

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products/{product}/links', [ProductController::class, 'generateLink'])
    ->middleware('throttle:30,1,generate-link')
    ->name('products.generate-link');
Route::get('/links', [LinkController::class, 'index'])->name('links.index');
Route::get('/promo-materials', [PromoMaterialController::class, 'index'])->name('promo-materials.index');
Route::get('/promo-materials/{promoMaterial}/download', [PromoMaterialController::class, 'download'])->name('promo-materials.download');
Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
Route::post('/withdrawals', [WithdrawalController::class, 'store'])
    ->middleware('throttle:10,1,withdrawals')
    ->name('withdrawals.store');
Route::get('/bank-account', [BankAccountController::class, 'edit'])->name('bank-account.edit');
Route::put('/bank-account', [BankAccountController::class, 'update'])->name('bank-account.update');
