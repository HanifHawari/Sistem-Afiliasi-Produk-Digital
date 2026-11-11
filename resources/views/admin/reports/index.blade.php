@php
    $points = $series['points'];
    $maxRevenue = max(array_column($points, 'revenue') ?: [0]);

    // Skala sumbu Y: 4 langkah dengan angka bulat (1 / 2 / 5 × 10^n)
    $niceStep = function (int $max): int {
        if ($max <= 0) {
            return 25000;
        }
        $raw = max(1, $max / 4);
        $magnitude = 10 ** floor(log10($raw));
        foreach ([1, 2, 5, 10] as $m) {
            if ($raw <= $m * $magnitude) {
                return max(1, (int) ($m * $magnitude));
            }
        }

        return max(1, (int) (10 * $magnitude));
    };
    $step = $niceStep($maxRevenue);
    $axisMax = $step * 4;
    $compact = fn (int $v) => match (true) {
        $v >= 1_000_000 => str_replace('.', ',', (string) round($v / 1_000_000, 1)).' jt',
        $v >= 1_000 => round($v / 1_000).' rb',
        default => (string) $v,
    };

    // Label sumbu X selektif supaya tidak bertabrakan: ±8 label di desktop, ±4 di HP
    $labelEvery = max(1, (int) ceil(count($points) / 8));
    $labelEveryMobile = max(1, (int) ceil(count($points) / 4));

    $delta = function (int $now, int $before): ?float {
        return $before > 0 ? ($now - $before) / $before * 100 : null;
    };
    $revenueDelta = $delta($summary['revenue'], $previous['revenue']);
    $ordersDelta = $delta($summary['orders'], $previous['orders']);

    $affiliateShare = $summary['revenue'] ? $summary['affiliate_revenue'] / $summary['revenue'] * 100 : 0;
    $rangeLabel = $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y');
    $query = $preset === 'custom' ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : ['preset' => $preset];

    $filterItems = collect($presets)->map(fn ($label, $key) => [
        'label' => $label,
        'href' => route('admin.reports.index', ['preset' => $key]),
        'active' => $preset === $key,
    ])->values()->all();
    if ($preset === 'custom') {
        array_unshift($filterItems, ['label' => 'Tanggal bebas', 'href' => route('admin.reports.index', $query), 'active' => true]);
    }
@endphp

<x-layouts.admin title="Laporan Penjualan">
    <x-page-header title="Laporan Penjualan" :description="'Pembayaran terverifikasi periode '.$rangeLabel.'.'">
        <x-slot name="actions">
            <a href="{{ route('admin.reports.export', $query) }}" class="btn-outline">Ekspor CSV</a>
        </x-slot>
    </x-page-header>

    {{-- Filter periode --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="toolbar">
        <x-filter-menu :items="$filterItems" />
        <div class="flex w-full items-center gap-2 sm:w-auto lg:ml-auto">
            <input type="date" name="start" value="{{ $start->toDateString() }}" aria-label="Tanggal mulai" class="input min-w-0 flex-1 lg:w-40">
            <span class="text-slate-400">–</span>
            <input type="date" name="end" value="{{ $end->toDateString() }}" aria-label="Tanggal akhir" class="input min-w-0 flex-1 lg:w-40">
            <button @class(['btn btn-sm py-2.5', 'bg-blue-600 text-white' => $preset === 'custom', 'border border-slate-200 bg-white text-slate-800 hover:bg-slate-50' => $preset !== 'custom'])>Terapkan</button>
        </div>
    </form>

    {{-- KPI --}}
    <x-stat-grid>
        @foreach ([
            ['Omzet', rupiah($summary['revenue']), 'Rata-rata '.rupiah($summary['average']).' per order', $revenueDelta],
            ['Order Terverifikasi', $summary['orders'].' order', 'Pembayaran yang disetujui admin', $ordersDelta],
            ['Komisi Afiliator', rupiah($summary['commission']), $summary['revenue'] ? round($summary['commission'] / $summary['revenue'] * 100, 1).'% dari omzet' : 'Belum ada omzet', false],
            ['Pendapatan Owner', rupiah($summary['owner']), 'Omzet dikurangi komisi afiliator', false],
        ] as [$label, $value, $caption, $change])
            <x-stat-card :label="$label" :value="$value" :caption="$caption">
                @if ($change !== false)
                    @if ($change === null)
                        <span class="text-white/80">Belum ada data pembanding</span>
                    @else
                        <span class="text-white/80">vs periode sebelumnya</span>
                        <span @class(['pill-on-blue', 'text-emerald-600' => $change >= 0, 'text-red-600' => $change < 0])>
                            {{ ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.') }}%
                        </span>
                    @endif
                @endif
            </x-stat-card>
        @endforeach
    </x-stat-grid>

    <div class="mb-6 grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Grafik tren --}}
        <section class="card 2xl:col-span-8" x-data="{ active: null, points: @js($points) }">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Omzet per {{ $series['unit'] }}</h2>
                    <p class="card-subtitle">{{ $rangeLabel }}</p>
                </div>
            </div>

            <div class="card-body">
                @if ($summary['orders'] === 0)
                    <div class="flex h-64 flex-col items-center justify-center text-center">
                        <p class="font-semibold text-slate-800">Belum ada penjualan di periode ini</p>
                        <p class="mt-1 text-sm text-slate-500">Coba pilih rentang waktu yang lebih panjang.</p>
                    </div>
                @else
                    <div class="flex gap-3">
                        {{-- Sumbu Y --}}
                        <div class="tabular flex h-56 flex-col justify-between text-right text-xs text-slate-400">
                            @for ($i = 4; $i >= 0; $i--)
                                <span class="-translate-y-1/2 leading-none first:translate-y-0 last:translate-y-0">{{ $compact($step * $i) }}</span>
                            @endfor
                        </div>

                        <div class="relative flex-1">
                            {{-- Garis bantu tipis --}}
                            <div class="pointer-events-none absolute inset-x-0 top-0 flex h-56 flex-col justify-between">
                                @for ($i = 0; $i <= 4; $i++)
                                    <div class="border-t border-slate-100"></div>
                                @endfor
                            </div>

                            {{-- Batang: area sorot = seluruh kolom (lebih besar dari batangnya) --}}
                            <div class="relative flex h-56 items-end gap-[2px]" @mouseleave="active = null">
                                @foreach ($points as $i => $point)
                                    <div class="flex h-full flex-1 cursor-default items-end justify-center outline-none focus-visible:bg-blue-50"
                                         tabindex="0" role="img"
                                         aria-label="{{ $point['full'] }}: {{ rupiah($point['revenue']) }}, {{ $point['orders'] }} order"
                                         @mouseenter="active = {{ $i }}" @focus="active = {{ $i }}" @blur="active = null">
                                        @if ($point['revenue'] > 0)
                                            <div class="w-full max-w-[24px] rounded-t bg-blue-600 transition-opacity"
                                                 :class="active !== null && active !== {{ $i }} && 'opacity-40'"
                                                 style="height: {{ max(2, $point['revenue'] / $axisMax * 100) }}%"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Tooltip --}}
                            <template x-if="active !== null">
                                <div class="pointer-events-none absolute -top-2 z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-xl bg-slate-900 px-3 py-2 text-white shadow-lg"
                                     :style="`left: ${(active + 0.5) / points.length * 100}%`">
                                    <p class="tabular text-sm font-bold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(points[active].revenue)"></p>
                                    <p class="text-xs text-white/70" x-text="points[active].full"></p>
                                    <p class="text-xs text-white/70" x-text="points[active].orders + ' order'"></p>
                                </div>
                            </template>

                            {{-- Sumbu X --}}
                            <div class="relative mt-2 h-4">
                                @foreach ($points as $i => $point)
                                    @php($desktop = $i % $labelEvery === 0)
                                    @php($mobile = $i % $labelEveryMobile === 0)
                                    @if ($desktop || $mobile)
                                        <span @class([
                                            'absolute -translate-x-1/2 whitespace-nowrap text-xs text-slate-400',
                                            'hidden sm:block' => $desktop && ! $mobile,
                                            'sm:hidden' => $mobile && ! $desktop,
                                        ]) style="left: {{ ($i + 0.5) / count($points) * 100 }}%">{{ $point['label'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Versi tabel dari grafik --}}
                    <details class="mt-6 rounded-xl border border-slate-200/80">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-blue-600">Lihat data dalam tabel</summary>
                        <div class="max-h-64 overflow-y-auto border-t border-slate-100">
                            <table class="tabular w-full text-left text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold">{{ ucfirst($series['unit']) }}</th>
                                        <th class="px-4 py-2 text-right font-semibold">Order</th>
                                        <th class="px-4 py-2 text-right font-semibold">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach ($points as $point)
                                        <tr>
                                            <td class="px-4 py-2">{{ $point['full'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ $point['orders'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ rupiah($point['revenue']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>
        </section>

        <div class="flex flex-col gap-6 2xl:col-span-4">
            {{-- Sumber penjualan: satu keseluruhan dibagi 2 bagian --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Sumber Penjualan</h2>
                        <p class="card-subtitle">Omzet dari link afiliator dibanding penjualan langsung</p>
                    </div>
                </div>
                <div class="card-body">
                    @if ($summary['revenue'] > 0)
                        <div class="flex h-11 w-full gap-[2px] overflow-hidden rounded-xl" role="img"
                             aria-label="Lewat afiliator {{ round($affiliateShare) }}%, organik {{ round(100 - $affiliateShare) }}%">
                            @if ($summary['affiliate_revenue'] > 0)
                                <div class="flex items-center bg-emerald-600 px-3 text-sm font-semibold text-white" style="width: {{ $affiliateShare }}%">
                                    {{ $affiliateShare >= 15 ? round($affiliateShare).'%' : '' }}
                                </div>
                            @endif
                            @if ($summary['organic_revenue'] > 0)
                                <div class="flex items-center bg-blue-600 px-3 text-sm font-semibold text-white" style="width: {{ 100 - $affiliateShare }}%">
                                    {{ 100 - $affiliateShare >= 15 ? round(100 - $affiliateShare).'%' : '' }}
                                </div>
                            @endif
                        </div>
                        <dl class="tabular mt-4 grid gap-2 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-600"></span> Lewat afiliator</dt>
                                <dd class="text-slate-800">{{ rupiah($summary['affiliate_revenue']) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span> Organik</dt>
                                <dd class="text-slate-800">{{ rupiah($summary['organic_revenue']) }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">Belum ada omzet di periode ini.</p>
                    @endif
                </div>
            </section>

            {{-- Pembagian hasil --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Pembagian Hasil</h2>
                </div>
                <dl class="card-body tabular grid gap-2 text-sm">
                    <div class="flex justify-between text-slate-600"><dt>Omzet</dt><dd class="font-semibold text-slate-800">{{ rupiah($summary['revenue']) }}</dd></div>
                    <div class="flex justify-between text-slate-600"><dt>Komisi afiliator</dt><dd>−{{ rupiah($summary['commission']) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2"><dt class="font-medium text-slate-800">Pendapatan owner</dt><dd class="font-bold text-blue-600">{{ rupiah($summary['owner']) }}</dd></div>
                </dl>
            </section>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Per produk --}}
        <section class="card overflow-hidden 2xl:col-span-7">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Penjualan per Produk</h2>
                    <p class="card-subtitle">Diurutkan dari omzet terbesar</p>
                </div>
            </div>
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Order</th>
                            <th>Omzet</th>
                            <th>Komisi</th>
                            <th>Owner</th>
                        </tr>
                    </thead>
                    <tbody class="tabular">
                        @forelse ($byProduct as $row)
                            <tr>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $row['product']->name }}</span>
                                    <div class="mt-2 h-1.5 w-full max-w-[200px] rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $summary['revenue'] ? $row['revenue'] / $summary['revenue'] * 100 : 0 }}%"></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">{{ $row['orders'] }}</td>
                                <td class="whitespace-nowrap font-bold text-slate-900">{{ rupiah($row['revenue']) }}</td>
                                <td class="whitespace-nowrap text-slate-500">{{ rupiah($row['commission']) }}</td>
                                <td class="whitespace-nowrap">{{ rupiah($row['owner']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-500">Belum ada penjualan di periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Per afiliator --}}
        <section class="card overflow-hidden 2xl:col-span-5">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Penjualan per Afiliator</h2>
                    <p class="card-subtitle">Hanya order dari link referral</p>
                </div>
            </div>
            <div class="relative overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Afiliator</th>
                            <th>Order</th>
                            <th>Omzet &amp; Komisi</th>
                        </tr>
                    </thead>
                    <tbody class="tabular">
                        @forelse ($byAffiliate as $row)
                            <tr>
                                <td><a href="{{ route('admin.affiliates.show', $row['affiliate']) }}" class="font-semibold text-slate-800 hover:text-blue-600">{{ $row['affiliate']->name }}</a></td>
                                <td class="whitespace-nowrap">{{ $row['orders'] }}</td>
                                <td class="whitespace-nowrap">
                                    <span class="block font-bold text-slate-900">{{ rupiah($row['revenue']) }}</span>
                                    <span class="block text-[13px] text-emerald-600">Komisi {{ rupiah($row['commission']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-10 text-center text-slate-500">Belum ada penjualan lewat afiliator.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
