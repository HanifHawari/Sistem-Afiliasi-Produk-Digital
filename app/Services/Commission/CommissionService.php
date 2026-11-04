<?php

namespace App\Services\Commission;

use App\Enums\CommissionStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\User;

/**
 * PIC: Muhammad Fadhil AL Fikry (feature/referral-tracking)
 *
 * Dipakai oleh:
 * - Verifikasi pembayaran admin (Hanif) -> createForOrder() setelah order jadi paid
 * - Dashboard afiliator (Gibran)        -> balanceFor()
 */
class CommissionService
{
    /**
     * Buat komisi untuk order yang sudah dibayar dan punya referral.
     * Return null jika order tanpa afiliator.
     */
    public function createForOrder(Order $order): ?Commission
    {
        // TODO (Fadhil): pastikan order sudah paid, cek afiliator aktif,
        //               dan bungkus dalam DB::transaction bila perlu.
        if (! $order->referral_link_id) {
            return null;
        }

        $order->loadMissing('product', 'referralLink');
        $rate = $order->product->commission_rate;

        return Commission::firstOrCreate(
            ['order_id' => $order->id], // 1 order hanya boleh 1 komisi
            [
                'affiliate_id' => $order->referralLink->affiliate_id,
                'rate' => $rate,
                'amount' => (int) round($order->amount * $rate / 100),
                'status' => CommissionStatus::Approved,
            ],
        );
    }

    /**
     * Saldo yang bisa dicairkan = komisi approved - pencairan yang tidak ditolak.
     */
    public function balanceFor(User $affiliate): int
    {
        $earned = $affiliate->commissions()
            ->where('status', CommissionStatus::Approved)
            ->sum('amount');

        $withdrawn = $affiliate->withdrawals()
            ->where('status', '!=', WithdrawalStatus::Rejected)
            ->sum('amount');

        return (int) ($earned - $withdrawn);
    }
}
