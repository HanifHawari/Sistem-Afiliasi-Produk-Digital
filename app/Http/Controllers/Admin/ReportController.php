<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan penjualan berdasarkan pembayaran yang sudah diverifikasi (status paid, tanggal verified_at).
 */
class ReportController extends Controller
{
    public const PRESETS = [
        '7d' => '7 hari',
        '30d' => '30 hari',
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'this_year' => 'Tahun ini',
    ];

    public function index(Request $request): View
    {
        [$start, $end, $preset] = $this->range($request);

        $orders = $this->paidOrders($start, $end)
            ->with(['product', 'referralLink.affiliate', 'commission'])
            ->get();

        // Periode sebelumnya dengan panjang yang sama, untuk perbandingan
        $days = $start->diffInDays($end) + 1;
        $previous = $this->summarize($this->paidOrders($start->subDays($days), $start->subSecond())->with('commission')->get());
        $summary = $this->summarize($orders);

        return view('admin.reports.index', [
            'start' => $start,
            'end' => $end,
            'preset' => $preset,
            'presets' => self::PRESETS,
            'summary' => $summary,
            'previous' => $previous,
            'series' => $this->series($orders, $start, $end),
            'byProduct' => $this->byProduct($orders),
            'byAffiliate' => $this->byAffiliate($orders),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->range($request);

        $orders = $this->paidOrders($start, $end)
            ->with(['customer', 'product', 'referralLink.affiliate', 'commission', 'paymentAccount'])
            ->orderBy('verified_at')
            ->get();

        $filename = 'laporan-penjualan-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8 dengan benar
            fputcsv($out, ['Invoice', 'Tanggal Verifikasi', 'Customer', 'Email', 'Produk', 'Afiliator', 'Kode Referral', 'Metode', 'Nominal', 'Komisi Afiliator', 'Bagian Owner'], ';');

            foreach ($orders as $order) {
                $commission = $this->commissionOf($order);
                fputcsv($out, [
                    $order->invoice_number,
                    $order->verified_at?->format('Y-m-d H:i'),
                    $order->customer->name,
                    $order->customer->email,
                    $order->product->name,
                    $order->referralLink?->affiliate->name ?? '-',
                    $order->referralLink?->code ?? '-',
                    $order->payment_method->label(),
                    $order->amount,
                    $commission,
                    $order->amount - $commission,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Ambil rentang tanggal dari preset atau tanggal bebas (start & end). Nilai tidak valid diabaikan.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function range(Request $request): array
    {
        $today = CarbonImmutable::today();
        $preset = is_string($preset = $request->query('preset')) ? $preset : '30d';

        $start = $this->parseDate($request->query('start'));
        $end = $this->parseDate($request->query('end'));

        if ($start && $end) {
            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }
            // Batasi maksimal 2 tahun supaya query tetap ringan
            $start = $start->max($end->subYears(2));

            return [$start->startOfDay(), $end->endOfDay(), 'custom'];
        }

        [$start, $end] = match ($preset) {
            '7d' => [$today->subDays(6), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->startOfYear(), $today],
            default => [$today->subDays(29), $today],
        };

        return [$start->startOfDay(), $end->endOfDay(), array_key_exists($preset, self::PRESETS) ? $preset : '30d'];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        // Tolak tanggal yang "meluber" seperti 2025-13-45
        return $date->format('Y-m-d') === $value ? $date : null;
    }

    private function paidOrders(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('verified_at', [$start, $end]);
    }

    private function commissionOf(Order $order): int
    {
        return $order->commission?->status === CommissionStatus::Approved ? (int) $order->commission->amount : 0;
    }

    /**
     * @return array{revenue: int, orders: int, commission: int, owner: int, average: int, affiliate_revenue: int, organic_revenue: int}
     */
    private function summarize(Collection $orders): array
    {
        $revenue = (int) $orders->sum('amount');
        $commission = (int) $orders->sum(fn (Order $o) => $this->commissionOf($o));
        $affiliateRevenue = (int) $orders->whereNotNull('referral_link_id')->sum('amount');

        return [
            'revenue' => $revenue,
            'orders' => $orders->count(),
            'commission' => $commission,
            'owner' => $revenue - $commission,
            'average' => $orders->count() ? (int) round($revenue / $orders->count()) : 0,
            'affiliate_revenue' => $affiliateRevenue,
            'organic_revenue' => $revenue - $affiliateRevenue,
        ];
    }

    /**
     * Data grafik: per hari, atau per bulan jika rentang lebih dari 62 hari.
     *
     * @return array{unit: string, points: list<array{key: string, label: string, full: string, revenue: int, orders: int}>}
     */
    private function series(Collection $orders, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $monthly = $start->diffInDays($end) > 62;
        $keyFormat = $monthly ? 'Y-m' : 'Y-m-d';
        $grouped = $orders->groupBy(fn (Order $o) => $o->verified_at->format($keyFormat));

        $period = CarbonPeriod::create($monthly ? $start->startOfMonth() : $start, $monthly ? '1 month' : '1 day', $end);

        $points = [];
        foreach ($period as $date) {
            $key = $date->format($keyFormat);
            $group = $grouped->get($key, collect());
            $points[] = [
                'key' => $key,
                'label' => $monthly ? $date->translatedFormat('M') : $date->translatedFormat('j M'),
                'full' => $monthly ? $date->translatedFormat('F Y') : $date->translatedFormat('l, d F Y'),
                'revenue' => (int) $group->sum('amount'),
                'orders' => $group->count(),
            ];
        }

        return ['unit' => $monthly ? 'bulan' : 'hari', 'points' => $points];
    }

    private function byProduct(Collection $orders): Collection
    {
        return $orders->groupBy('product_id')
            ->map(function (Collection $group) {
                $revenue = (int) $group->sum('amount');
                $commission = (int) $group->sum(fn (Order $o) => $this->commissionOf($o));

                return [
                    'product' => $group->first()->product,
                    'orders' => $group->count(),
                    'revenue' => $revenue,
                    'commission' => $commission,
                    'owner' => $revenue - $commission,
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    private function byAffiliate(Collection $orders): Collection
    {
        return $orders->filter(fn (Order $o) => $o->referralLink !== null)
            ->groupBy(fn (Order $o) => $o->referralLink->affiliate_id)
            ->map(fn (Collection $group) => [
                'affiliate' => $group->first()->referralLink->affiliate,
                'orders' => $group->count(),
                'revenue' => (int) $group->sum('amount'),
                'commission' => (int) $group->sum(fn (Order $o) => $this->commissionOf($o)),
            ])
            ->sortByDesc('revenue')
            ->values();
    }
}
