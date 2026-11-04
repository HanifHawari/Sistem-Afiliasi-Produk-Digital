<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola afiliator: daftar, performa, detail, aktif/nonaktif.
 */
class AffiliateController extends Controller
{
    private const SORTS = [
        'latest' => 'Terbaru',
        'sales' => 'Penjualan terbanyak',
        'commission' => 'Komisi terbesar',
        'clicks' => 'Klik terbanyak',
    ];

    public function index(Request $request): View
    {
        // Filter yang tidak valid diabaikan
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : 'all';
        $sort = in_array($request->query('sort'), array_keys(self::SORTS), true) ? $request->query('sort') : 'latest';
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);

        $affiliates = $this->withPerformance(User::query()->where('role', UserRole::Affiliate))
            ->with('affiliateProfile')
            ->when($status !== 'all', fn (Builder $q) => $q->where('is_active', $status === 'active'))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($sort === 'sales', fn (Builder $q) => $q->orderByDesc('sales_count'))
            ->when($sort === 'commission', fn (Builder $q) => $q->orderByDesc('earned'))
            ->when($sort === 'clicks', fn (Builder $q) => $q->orderByDesc('referral_clicks_count'))
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $base = User::where('role', UserRole::Affiliate);
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'new_month' => (clone $base)->where('created_at', '>=', now()->startOfMonth())->count(),
            'with_sales' => (clone $base)->whereHas('referralOrders', fn ($q) => $q->where('orders.status', OrderStatus::Paid))->count(),
            'commission_total' => (int) Commission::where('status', CommissionStatus::Approved)->sum('amount'),
        ];

        $sorts = self::SORTS;

        return view('admin.affiliates.index', compact('affiliates', 'stats', 'status', 'sort', 'sorts', 'search'));
    }

    public function show(User $affiliate): View
    {
        abort_unless($affiliate->isAffiliate(), 404);

        $affiliate = $this->withPerformance(User::whereKey($affiliate->id))
            ->with('affiliateProfile')
            ->firstOrFail();

        $links = $affiliate->referralLinks()
            ->with('product')
            ->withCount('clicks')
            ->withCount(['orders as sales_count' => fn ($q) => $q->where('status', OrderStatus::Paid)])
            ->withSum(['orders as revenue' => fn ($q) => $q->where('status', OrderStatus::Paid)], 'amount')
            ->orderByDesc('sales_count')
            ->get();

        $commissions = $affiliate->commissions()
            ->with('order.product')
            ->latest()
            ->limit(10)
            ->get();

        $withdrawals = $affiliate->withdrawals()->latest()->limit(10)->get();

        return view('admin.affiliates.show', compact('affiliate', 'links', 'commissions', 'withdrawals'));
    }

    public function toggle(User $affiliate): RedirectResponse
    {
        abort_unless($affiliate->isAffiliate(), 404);

        $affiliate->forceFill(['is_active' => ! $affiliate->is_active])->save();

        return back()
            ->with('status', $affiliate->is_active ? "{$affiliate->name} diaktifkan" : "{$affiliate->name} dinonaktifkan")
            ->with('status_detail', $affiliate->is_active
                ? 'Afiliator bisa login dan mendapat komisi lagi.'
                : 'Afiliator tidak bisa login sampai diaktifkan kembali.');
    }

    /**
     * Tambahkan angka performa: jumlah link, klik, penjualan, omzet, komisi, dan total pencairan.
     * Saldo = earned - withdrawn (sama dengan CommissionService::balanceFor()).
     */
    private function withPerformance(Builder $query): Builder
    {
        return $query
            ->withCount(['referralLinks', 'referralClicks'])
            ->withCount(['referralOrders as sales_count' => fn ($q) => $q->where('orders.status', OrderStatus::Paid)])
            ->withSum(['referralOrders as revenue' => fn ($q) => $q->where('orders.status', OrderStatus::Paid)], 'amount')
            ->withSum(['commissions as earned' => fn ($q) => $q->where('status', CommissionStatus::Approved)], 'amount')
            ->withSum(['withdrawals as withdrawn' => fn ($q) => $q->where('status', '!=', WithdrawalStatus::Rejected)], 'amount')
            ->withSum(['withdrawals as paid_out' => fn ($q) => $q->where('status', WithdrawalStatus::Paid)], 'amount');
    }
}
