<?php

namespace App\Http\Controllers\Affiliate;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ReferralLink;
use App\Services\Referral\ReferralStatistics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LinkController extends Controller
{
    public function __construct(
        private readonly ReferralStatistics $referralStatistics,
    ) {}

    /**
     * Tampilkan daftar referral link dan kampanye milik afiliator yang sedang login.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Statistik KPI Keseluruhan dari ReferralStatistics (summaryFor)
        $statisticsSummary = $this->referralStatistics->summaryFor($user);
        $summary = [
            'total_links' => $user->referralLinks()->count(),
            'clicks' => $statisticsSummary['clicks'],
            'paid_orders' => $statisticsSummary['paid_orders'],
            'conversion_rate' => $statisticsSummary['conversion_rate'],
        ];

        // 2. Filter & Parameter Pencarian
        $search = mb_substr(trim(is_string($q = $request->query('q')) ? $q : ''), 0, 100);
        $selectedProduct = $request->filled('product_id') ? (int) $request->query('product_id') : null;

        // 3. Query Referral Links milik Afiliator
        $linksQuery = $user->referralLinks()
            ->with('product')
            ->withCount('clicks')
            ->withCount([
                'orders as paid_orders_count' => fn (Builder $query) => $query->where('status', OrderStatus::Paid),
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(! empty($selectedProduct), function (Builder $query) use ($selectedProduct) {
                $query->where('product_id', $selectedProduct);
            })
            ->latest();

        $links = $linksQuery->paginate(10)->withQueryString();

        // 4. Hitung Conversion Rate per Link (paid_orders_count / clicks_count * 100, 2 desimal)
        $links->getCollection()->each(function (ReferralLink $link) {
            $link->setAttribute(
                'conversion_rate',
                $link->clicks_count > 0
                    ? round(($link->paid_orders_count / $link->clicks_count) * 100, 2)
                    : 0.0
            );
        });

        // 5. Data Quick Link Generator: Produk aktif yang belum memiliki referral link untuk afiliator ini
        $productsWithoutLink = Product::active()
            ->whereDoesntHave('referralLinks', fn (Builder $query) => $query->where('affiliate_id', $user->id))
            ->orderBy('name')
            ->get();

        // 6. Produk untuk opsi filter (produk yang sudah dimiliki link-nya oleh afiliator)
        $userProducts = Product::whereHas('referralLinks', fn (Builder $query) => $query->where('affiliate_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('affiliate.links.index', compact(
            'summary',
            'links',
            'search',
            'selectedProduct',
            'productsWithoutLink',
            'userProducts',
        ));
    }
}

