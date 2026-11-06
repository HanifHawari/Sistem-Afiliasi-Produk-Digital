<?php

use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Halaman toko (/) ada di routes/shop.php, admin di routes/admin.php,
// afiliator di routes/affiliate.php.

// Setelah login, arahkan ke halaman utama sesuai role
Route::get('/dashboard', fn (Request $request) => redirect($request->user()->homeRoute()))
    ->middleware('auth')->name('dashboard');

// Link referral afiliator, mis. /r/ANDI123  —  PIC: Fadhil (feature/referral-tracking)
Route::get('/r/{code}', [ReferralController::class, 'redirect'])->name('referral.redirect');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');
});

require __DIR__.'/auth.php';
