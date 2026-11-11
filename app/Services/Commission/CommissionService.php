<?php

namespace App\Services\Commission;

use App\Enums\CommissionStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($order) {
            // Baca ulang agar status/relasi lama pada instance pemanggil tidak dipakai.
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order || ! $order->isPaid() || ! $order->referral_link_id) {
                return null;
            }

            // Pemanggilan ulang mempertahankan nominal komisi yang sudah tercatat.
            if ($existing = $order->commission()->first()) {
                return $existing;
            }

            $order->load('product', 'referralLink.affiliate');
            $link = $order->referralLink;
            $affiliate = $link?->affiliate;

            if (! $affiliate || ! $affiliate->isAffiliate() || ! $affiliate->is_active
                || $affiliate->id === $order->customer_id
                || $link->product_id !== $order->product_id || ! $order->product) {
                return null;
            }

            $rate = $order->product->commission_rate;

            if ($rate < 0 || $rate > 100 || $order->amount < 0) {
                return null;
            }

            return Commission::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'affiliate_id' => $affiliate->id,
                    'rate' => $rate,
                    'amount' => (int) round($order->amount * $rate / 100),
                    'status' => CommissionStatus::Approved,
                ],
            );
        });
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
