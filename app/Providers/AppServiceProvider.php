<?php

namespace App\Providers;

use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\Withdrawal;
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
        // Jumlah antrean untuk badge sidebar & notifikasi di layout admin
        View::composer('components.layouts.admin', function ($view) {
            $view->with([
                'pendingOrders' => Order::where('status', OrderStatus::WaitingVerification)->count(),
                'pendingWithdrawals' => Withdrawal::where('status', WithdrawalStatus::Pending)->count(),
            ]);
        });
    }
}
