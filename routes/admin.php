<?php

use Illuminate\Support\Facades\Route;

/*
| Area Admin  —  PIC: Muhammad Hanif Hawari (feature/admin-dashboard)
| Prefix URL /admin, nama route admin.*, middleware auth + role:admin
| (diatur di bootstrap/app.php). Controller taruh di app/Http/Controllers/Admin.
| Route::view di bawah hanya placeholder, ganti dengan controller.
*/

Route::view('/', 'admin.dashboard')->name('dashboard');
Route::view('/products', 'admin.products.index')->name('products.index');
Route::view('/affiliates', 'admin.affiliates.index')->name('affiliates.index');
Route::view('/orders', 'admin.orders.index')->name('orders.index');
Route::view('/withdrawals', 'admin.withdrawals.index')->name('withdrawals.index');
Route::view('/payment-accounts', 'admin.payment-accounts.index')->name('payment-accounts.index');
Route::view('/reports', 'admin.reports.index')->name('reports.index');
