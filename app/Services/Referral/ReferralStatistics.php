<?php

namespace App\Services\Referral;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\Commission\CommissionService;
use Illuminate\Database\Eloquent\Collection;

class ReferralStatistics
{
    public function __construct(private CommissionService $commissions) {}

    /**
     * Konversi = order paid / klik tercatat x 100 (bukan jumlah pengunjung unik).
     *
     * @return array{clicks: int, orders: int, paid_orders: int, conversion_rate: float, revenue: int, commission: int, balance: int}
     */
    public function summaryFor(User $affiliate): array
    {
        $clicks = $affiliate->referralClicks()->count();
        $paidOrders = $affiliate->referralOrders()->where('status', OrderStatus::Paid);
        $paidCount = (clone $paidOrders)->count();

        return [
            'clicks' => $clicks,
            'orders' => $affiliate->referralOrders()->count(),
            'paid_orders' => $paidCount,
            'conversion_rate' => $clicks > 0 ? round($paidCount / $clicks * 100, 2) : 0.0,
            'revenue' => (int) $paidOrders->sum('amount'),
            'commission' => (int) $affiliate->commissions()->where('status', CommissionStatus::Approved)->sum('amount'),
            'balance' => $this->commissions->balanceFor($affiliate),
        ];
    }

    /** Link dilengkapi clicks_count, orders_count, paid_orders_count, dan conversion_rate. */
    public function linksFor(User $affiliate): Collection
    {
        return $affiliate->referralLinks()->with('product')
            ->withCount(['clicks', 'orders', 'orders as paid_orders_count' => fn ($query) => $query->where('status', OrderStatus::Paid)])
            ->orderBy('id')->get()
            ->each(function ($link) {
                $link->setAttribute('conversion_rate', $link->clicks_count > 0
                    ? round($link->paid_orders_count / $link->clicks_count * 100, 2) : 0.0);
            });
    }
}
