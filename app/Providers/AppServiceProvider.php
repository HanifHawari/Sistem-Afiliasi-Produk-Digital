<?php

namespace App\Providers;

use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\Withdrawal;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proxy tepercaya dari .env (TRUSTED_PROXIES), lihat config/app.php
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Jumlah antrean untuk badge sidebar & notifikasi di layout admin
        View::composer('components.layouts.admin', function ($view) {
            $view->with([
                'pendingOrders' => Order::where('status', OrderStatus::WaitingVerification)->count(),
                'pendingWithdrawals' => Withdrawal::where('status', WithdrawalStatus::Pending)->count(),
            ]);
        });
    }
}
