<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Services\Commission\CommissionService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CommissionService $commissionService,
    ) {}

    public function __invoke(): View
    {
        $user = auth()->user();

        // 1. Total Klik — semua klik dari referral link milik afiliator ini
        $totalClicks = $user->referralClicks()->count();

        // 2. Total Penjualan — order berstatus Paid yang datang dari referral afiliator
        $totalSales = $user->referralOrders()
            ->where('status', OrderStatus::Paid)
            ->count();

        // 3. Saldo Siap Cair — wajib pakai CommissionService, jangan duplikasi rumus
        $balance = $this->commissionService->balanceFor($user);

        // 4. Total Komisi — akumulasi nominal komisi berstatus Approved
        $totalCommission = $user->commissions()
            ->where('status', CommissionStatus::Approved)
            ->sum('amount');

        // 5. Riwayat Penjualan — 5 order referral terbaru berstatus Paid
        //    Eager load untuk mencegah N+1: customer (nama pembeli), product (nama produk), commission (nominal komisi)
        $recentSales = $user->referralOrders()
            ->with(['customer', 'product', 'commission'])
            ->where('status', OrderStatus::Paid)
            ->latest()
            ->limit(5)
            ->get();

        return view('affiliate.dashboard', compact(
            'totalClicks',
            'totalSales',
            'balance',
            'totalCommission',
            'recentSales',
        ));
    }
}
