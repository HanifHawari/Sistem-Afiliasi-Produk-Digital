<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Admin\Concerns\ServesProofFiles;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Order;
use App\Services\Commission\CommissionService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verifikasi pembayaran customer (desain: design/stitch/hanif_admin-dashboard_verifikasi-pembayaran)
 */
class OrderController extends Controller
{
    use ServesProofFiles;

    private const RELATIONS = ['customer', 'product', 'referralLink.affiliate', 'paymentAccount', 'commission', 'verifier'];

    public function index(Request $request): View
    {
        // Nilai filter yang tidak valid diabaikan (tidak redirect, supaya tidak berputar-putar)
        $status = $request->query('status');
        if (! in_array($status, ['all', ...array_column(OrderStatus::cases(), 'value')], true)) {
            $status = OrderStatus::WaitingVerification->value;
        }
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);
        $selectedId = filter_var($request->query('order'), FILTER_VALIDATE_INT) ?: null;

        $orders = Order::with(self::RELATIONS)
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $c) => $c
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('referralLink', fn (Builder $r) => $r->where('code', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $selected = $selectedId
            ? Order::with(self::RELATIONS)->find($selectedId)
            : $orders->first();

        $counts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'volume_today' => (int) Order::where('status', OrderStatus::Paid)->whereDate('verified_at', today())->sum('amount'),
            'waiting_count' => $counts[OrderStatus::WaitingVerification->value] ?? 0,
            'waiting_oldest' => Order::where('status', OrderStatus::WaitingVerification)->oldest()->value('created_at'),
            'commission_month' => (int) Commission::where('status', CommissionStatus::Approved)
                ->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'paid_count' => $counts[OrderStatus::Paid->value] ?? 0,
        ];

        return view('admin.orders.index', compact('orders', 'selected', 'counts', 'stats', 'status', 'search'));
    }

    public function approve(Request $request, Order $order, CommissionService $commissions): RedirectResponse
    {
        $commission = null;

        $approved = $this->verify($order, function (Order $order) use ($commissions, $request, &$commission) {
            $order->update([
                'status' => OrderStatus::Paid,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'rejection_note' => null,
            ]);

            $commission = $commissions->createForOrder($order);
        });

        if (! $approved) {
            return back()->with('error', "Pesanan {$order->invoice_number} sudah diproses sebelumnya.");
        }

        $detail = 'Akses produk sudah terbuka untuk customer.';
        if ($commission) {
            $detail .= ' Komisi '.rupiah($commission->amount).' dicatat untuk '.$order->referralLink->affiliate->name.'.';
        }

        return back()
            ->with('status', "Pembayaran {$order->invoice_number} terverifikasi")
            ->with('status_detail', $detail);
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ], [], ['rejection_note' => 'alasan penolakan']);

        $rejected = $this->verify($order, fn (Order $order) => $order->update([
            'status' => OrderStatus::Rejected,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_note' => $data['rejection_note'],
        ]));

        if (! $rejected) {
            return back()->with('error', "Pesanan {$order->invoice_number} sudah diproses sebelumnya.");
        }

        return back()
            ->with('status', "Pembayaran {$order->invoice_number} ditolak")
            ->with('status_detail', 'Alasan penolakan akan terlihat oleh customer.');
    }

    /**
     * Tampilkan bukti bayar (file privat, hanya admin).
     */
    public function proof(Order $order): StreamedResponse
    {
        return $this->proofResponse($order->payment_proof);
    }

    /**
     * Jalankan keputusan admin hanya jika pesanan masih menunggu keputusan.
     * Barisnya dikunci dan statusnya dibaca ulang di dalam transaksi, supaya dua permintaan
     * bersamaan (klik dobel, atau setujui dan tolak sekaligus) tidak sama-sama diproses.
     *
     * @param  Closure(Order): mixed  $decide
     */
    private function verify(Order $order, Closure $decide): bool
    {
        return DB::transaction(function () use ($order, $decide) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || ! in_array($locked->status, [OrderStatus::Pending, OrderStatus::WaitingVerification], true)) {
                return false;
            }

            $decide($locked);

            return true;
        });
    }
}
