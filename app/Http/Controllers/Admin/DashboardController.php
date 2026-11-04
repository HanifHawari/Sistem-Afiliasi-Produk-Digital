<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();
        $paidThisMonth = Order::where('status', OrderStatus::Paid)->where('verified_at', '>=', $monthStart);

        // Pembanding: periode yang sama di bulan lalu (tanggal 1 s/d hari ini bulan lalu)
        $revenueLastMonth = (int) Order::where('status', OrderStatus::Paid)
            ->whereBetween('verified_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()])
            ->sum('amount');

        $stats = [
            'revenue_last_month' => $revenueLastMonth,
            'revenue_month' => (int) (clone $paidThisMonth)->sum('amount'),
            'orders_month' => (clone $paidThisMonth)->count(),
            'waiting_count' => Order::where('status', OrderStatus::WaitingVerification)->count(),
            'waiting_oldest' => Order::where('status', OrderStatus::WaitingVerification)->oldest()->value('created_at'),
            'commission_month' => (int) Commission::where('status', CommissionStatus::Approved)
                ->where('created_at', '>=', $monthStart)->sum('amount'),
            'withdrawal_pending_sum' => (int) Withdrawal::where('status', WithdrawalStatus::Pending)->sum('amount'),
            'withdrawal_pending_count' => Withdrawal::where('status', WithdrawalStatus::Pending)->count(),
            'active_products' => Product::active()->count(),
            'affiliates' => User::where('role', UserRole::Affiliate)->count(),
        ];

        $waitingOrders = Order::with(['customer', 'product', 'referralLink.affiliate'])
            ->where('status', OrderStatus::WaitingVerification)
            ->oldest()
            ->limit(5)
            ->get();

        // Produk terlaris bulan ini
        $paidMonth = fn ($q) => $q->where('status', OrderStatus::Paid)->where('verified_at', '>=', $monthStart);
        $topProducts = Product::query()
            ->withCount(['orders as sold_count' => $paidMonth])
            ->withSum(['orders as revenue' => $paidMonth], 'amount')
            ->orderByDesc('sold_count')
            ->orderByDesc('revenue')
            ->limit(4)
            ->get();

        $pendingWithdrawals = Withdrawal::with('affiliate')
            ->where('status', WithdrawalStatus::Pending)
            ->oldest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'waitingOrders', 'topProducts', 'pendingWithdrawals'));
    }
}
