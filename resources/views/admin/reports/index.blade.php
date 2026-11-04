@php
    $points = $series['points'];
    $maxRevenue = max(array_column($points, 'revenue') ?: [0]);

    // Skala sumbu Y: 4 langkah dengan angka bulat (1 / 2 / 5 × 10^n)
    $niceStep = function (int $max): int {
        if ($max <= 0) {
            return 25000;
        }
        $raw = $max / 4;
        $magnitude = 10 ** floor(log10($raw));
        foreach ([1, 2, 5, 10] as $m) {
            if ($raw <= $m * $magnitude) {
                return (int) ($m * $magnitude);
            }
        }

        return (int) (10 * $magnitude);
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
@endphp

<x-layouts.admin title="Laporan Penjualan">
    <x-page-header eyebrow="Ringkasan" title="Laporan Penjualan"
        :description="'Pembayaran terverifikasi periode '.$rangeLabel.'.'">
        <x-slot name="actions">
            <a href="{{ route('admin.reports.export', $query) }}" class="flex items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-md py-2.5 text-on-surface shadow-sm transition-all hover:bg-surface-container">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">download</span>
                <span class="font-label-md text-label-md">Ekspor CSV</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- Filter periode: satu baris di atas semua angka & grafik --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-space-lg flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-surface-container-low p-1.5">
        <div class="no-scrollbar flex w-full items-center gap-1 overflow-x-auto sm:w-auto sm:flex-wrap">
            @foreach ($presets as $key => $label)
                <a href="{{ route('admin.reports.index', ['preset' => $key]) }}"
                   @if ($preset === $key) x-data x-init="$el.parentElement.scrollLeft = $el.offsetLeft - 24" @endif
                   @class([
                       'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-space-md py-1.5 font-label-md text-label-md transition-colors',
                       'bg-surface-container-lowest font-semibold text-primary shadow-sm' => $preset === $key,
                       'text-on-surface-variant hover:text-on-surface' => $preset !== $key,
                   ])>
                    @if ($preset === $key)
                        <span class="h-2 w-2 rounded-full bg-tertiary"></span>
                    @endif
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="flex w-full items-center gap-space-xs sm:w-auto">
            <input type="date" name="start" value="{{ $start->toDateString() }}" aria-label="Tanggal mulai"
                   class="min-w-0 flex-1 rounded-lg border-0 bg-surface-container-lowest py-1.5 font-body-sm text-body-sm focus:ring-2 focus:ring-primary-container">
            <span class="text-on-surface-variant">–</span>
            <input type="date" name="end" value="{{ $end->toDateString() }}" aria-label="Tanggal akhir"
                   class="min-w-0 flex-1 rounded-lg border-0 bg-surface-container-lowest py-1.5 font-body-sm text-body-sm focus:ring-2 focus:ring-primary-container">
            <button @class([
                'rounded-lg px-space-md py-1.5 font-label-md text-label-md shadow-sm',
                'bg-primary-container text-on-primary' => $preset === 'custom',
                'bg-surface-container-lowest text-on-surface hover:bg-surface-container' => $preset !== 'custom',
            ])>Terapkan</button>
        </div>
    </form>

    {{-- KPI --}}
    <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
        @foreach ([
            ['Omzet', 'payments', 'primary', rupiah($summary['revenue']), 'Rata-rata '.rupiah($summary['average']).' per order', $revenueDelta, 'text-on-surface'],
            ['Order Terverifikasi', 'receipt_long', 'tertiary', $summary['orders'].' order', 'Pembayaran yang disetujui admin', $ordersDelta, 'text-on-surface'],
            ['Komisi Afiliator', 'handshake', 'secondary', rupiah($summary['commission']), $summary['revenue'] ? round($summary['commission'] / $summary['revenue'] * 100, 1).'% dari omzet' : 'Belum ada omzet', false, 'text-secondary'],
            ['Pendapatan Owner', 'account_balance', 'primary', rupiah($summary['owner']), 'Omzet dikurangi komisi afiliator', false, 'text-primary'],
        ] as [$label, $icon, $tone, $value, $caption, $change, $valueClass])
            <x-stat-card :label="$label" :icon="$icon" :tone="$tone" :value="$value" :caption="$caption" :value-class="$valueClass">
                @if ($change !== false)
                    @if ($change === null)
                        <span class="text-on-surface-variant">Belum ada data pembanding</span>
                    @else
                        <span @class(['flex items-center gap-1', 'text-secondary' => $change >= 0, 'text-error' => $change < 0])>
                            <span class="material-symbols-outlined text-[16px]">{{ $change >= 0 ? 'trending_up' : 'trending_down' }}</span>
                            {{ ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.') }}% vs periode sebelumnya
                        </span>
                    @endif
                @endif
            </x-stat-card>
        @endforeach
    </div>

    <div class="mb-space-lg grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Grafik tren --}}
        <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm xl:col-span-8"
                 x-data="{ active: null, points: @js($points) }">
            <div class="mb-space-md flex items-start justify-between gap-2">
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Omzet per {{ $series['unit'] }}</h2>
                    <p class="font-caption text-caption text-on-surface-variant">{{ $rangeLabel }} · arahkan kursor ke batang untuk detail</p>
                </div>
            </div>

            @if ($summary['orders'] === 0)
                <div class="flex h-64 flex-col items-center justify-center gap-1 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline">bar_chart</span>
                    <span class="font-label-md text-label-md text-on-surface">Belum ada penjualan di periode ini</span>
                    <span class="font-caption text-caption">Coba pilih rentang waktu yang lebih panjang.</span>
                </div>
            @else
                <div class="flex gap-space-sm">
                    {{-- Sumbu Y --}}
                    <div class="tabular flex h-56 flex-col justify-between pb-0 text-right font-caption text-caption text-on-surface-variant">
                        @for ($i = 4; $i >= 0; $i--)
                            <span class="-translate-y-1/2 leading-none first:translate-y-0 last:translate-y-0">{{ $compact($step * $i) }}</span>
                        @endfor
                    </div>

                    <div class="relative flex-1">
                        {{-- Garis bantu tipis --}}
                        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-56 flex-col justify-between">
                            @for ($i = 0; $i <= 4; $i++)
                                <div class="border-t border-slate-200"></div>
                            @endfor
                        </div>

                        {{-- Batang: area sorot = seluruh kolom (lebih besar dari batangnya) --}}
                        <div class="relative flex h-56 items-end gap-[2px]" @mouseleave="active = null">
                            @foreach ($points as $i => $point)
                                <div class="flex h-full flex-1 cursor-default items-end justify-center outline-none focus-visible:bg-primary-fixed/40"
                                     tabindex="0" role="img"
                                     aria-label="{{ $point['full'] }}: {{ rupiah($point['revenue']) }}, {{ $point['orders'] }} order"
                                     @mouseenter="active = {{ $i }}" @focus="active = {{ $i }}" @blur="active = null">
                                    @if ($point['revenue'] > 0)
                                        <div class="w-full max-w-[24px] rounded-t bg-[#4f46e5] transition-opacity"
                                             :class="active !== null && active !== {{ $i }} && 'opacity-40'"
                                             style="height: {{ max(2, $point['revenue'] / $axisMax * 100) }}%"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Tooltip --}}
                        <template x-if="active !== null">
                            <div class="pointer-events-none absolute -top-2 z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-inverse-surface px-3 py-2 text-inverse-on-surface shadow-lg"
                                 :style="`left: ${(active + 0.5) / points.length * 100}%`">
                                <p class="tabular font-label-md text-label-md font-bold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(points[active].revenue)"></p>
                                <p class="font-caption text-caption text-inverse-on-surface/80" x-text="points[active].full"></p>
                                <p class="font-caption text-caption text-inverse-on-surface/80" x-text="points[active].orders + ' order'"></p>
                            </div>
                        </template>

                        {{-- Sumbu X --}}
                        <div class="relative mt-1.5 h-4">
                            @foreach ($points as $i => $point)
                                @php($desktop = $i % $labelEvery === 0)
                                @php($mobile = $i % $labelEveryMobile === 0)
                                @if ($desktop || $mobile)
                                    <span @class([
                                        'absolute -translate-x-1/2 whitespace-nowrap font-caption text-caption text-on-surface-variant',
                                        'hidden sm:block' => $desktop && ! $mobile,
                                        'sm:hidden' => $mobile && ! $desktop,
                                    ]) style="left: {{ ($i + 0.5) / count($points) * 100 }}%">{{ $point['label'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Versi tabel dari grafik --}}
                <details class="mt-space-md rounded-lg bg-surface-container-low">
                    <summary class="cursor-pointer px-space-sm py-2 font-label-sm text-label-sm text-primary">Lihat data dalam tabel</summary>
                    <div class="max-h-64 overflow-y-auto">
                        <table class="tabular w-full text-left font-body-sm text-body-sm">
                            <thead class="sticky top-0 bg-surface-container-low">
                                <tr class="text-on-surface-variant">
                                    <th class="px-space-sm py-1.5 font-semibold">{{ ucfirst($series['unit']) }}</th>
                                    <th class="px-space-sm py-1.5 text-right font-semibold">Order</th>
                                    <th class="px-space-sm py-1.5 text-right font-semibold">Omzet</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container">
                                @foreach ($points as $point)
                                    <tr>
                                        <td class="px-space-sm py-1.5">{{ $point['full'] }}</td>
                                        <td class="px-space-sm py-1.5 text-right">{{ $point['orders'] }}</td>
                                        <td class="px-space-sm py-1.5 text-right">{{ rupiah($point['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
        </section>

        <div class="flex flex-col gap-space-md xl:col-span-4">
            {{-- Sumber penjualan: satu keseluruhan dibagi 2 bagian --}}
            <section class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Sumber Penjualan</h3>
                <p class="font-caption text-caption text-on-surface-variant">Porsi omzet dari link afiliator dibanding penjualan langsung</p>

                @if ($summary['revenue'] > 0)
                    <div class="flex h-12 w-full gap-[2px] overflow-hidden rounded-lg" role="img"
                         aria-label="Lewat afiliator {{ round($affiliateShare) }}%, organik {{ round(100 - $affiliateShare) }}%">
                        @if ($summary['affiliate_revenue'] > 0)
                            <div class="flex items-center bg-[#059669] px-2 font-label-sm text-label-sm text-white" style="width: {{ $affiliateShare }}%">
                                {{ $affiliateShare >= 15 ? round($affiliateShare).'%' : '' }}
                            </div>
                        @endif
                        @if ($summary['organic_revenue'] > 0)
                            <div class="flex items-center bg-[#4f46e5] px-2 font-label-sm text-label-sm text-white" style="width: {{ 100 - $affiliateShare }}%">
                                {{ 100 - $affiliateShare >= 15 ? round(100 - $affiliateShare).'%' : '' }}
                            </div>
                        @endif
                    </div>
                    <dl class="flex flex-col gap-1.5 font-body-sm text-body-sm">
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-1.5 text-on-surface"><span class="h-2.5 w-2.5 rounded-sm bg-[#059669]"></span> Lewat afiliator</dt>
                            <dd class="tabular text-on-surface">{{ rupiah($summary['affiliate_revenue']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="flex items-center gap-1.5 text-on-surface"><span class="h-2.5 w-2.5 rounded-sm bg-[#4f46e5]"></span> Organik (tanpa afiliator)</dt>
                            <dd class="tabular text-on-surface">{{ rupiah($summary['organic_revenue']) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="rounded-lg bg-surface-container-low p-space-sm font-body-sm text-body-sm text-on-surface-variant">Belum ada omzet di periode ini.</p>
                @endif
            </section>

            {{-- Pembagian hasil --}}
            <section class="flex flex-col gap-space-xs rounded-xl bg-surface-container-low p-space-md">
                <span class="font-label-sm text-label-sm font-bold text-on-surface">Pembagian Hasil</span>
                <div class="tabular mt-1 flex flex-col gap-1.5 font-body-sm text-body-sm">
                    <div class="flex justify-between text-on-surface"><span>Omzet</span><span class="font-semibold">{{ rupiah($summary['revenue']) }}</span></div>
                    <div class="flex justify-between text-on-surface-variant"><span>Komisi afiliator</span><span>−{{ rupiah($summary['commission']) }}</span></div>
                    <div class="flex justify-between border-t border-outline-variant/50 pt-1.5 font-medium text-primary"><span>Pendapatan owner</span><span class="font-bold">{{ rupiah($summary['owner']) }}</span></div>
                </div>
            </section>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Per produk --}}
        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-7">
            <div class="p-space-md">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Penjualan per Produk</h2>
                <p class="font-caption text-caption text-on-surface-variant">Diurutkan dari omzet terbesar</p>
            </div>
            <div class="overflow-x-auto">
                <table class="ledger w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-space-md py-3">Produk</th>
                            <th class="px-space-md py-3 text-right">Order</th>
                            <th class="px-space-md py-3 text-right">Omzet</th>
                            <th class="px-space-md py-3 text-right">Komisi</th>
                            <th class="px-space-md py-3 text-right">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="tabular divide-y divide-surface-container-low">
                        @forelse ($byProduct as $row)
                            <tr class="hover:bg-surface-container-low/60">
                                <td class="px-space-md py-3">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $row['product']->name }}</p>
                                    {{-- Porsi omzet: bar tipis satu warna, angka ada di kolom Omzet --}}
                                    <div class="mt-1 h-1.5 w-full max-w-[200px] rounded-full bg-surface-container-high">
                                        <div class="h-full rounded-full bg-[#4f46e5]" style="width: {{ $summary['revenue'] ? $row['revenue'] / $summary['revenue'] * 100 : 0 }}%"></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right">{{ $row['orders'] }}</td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right font-label-md text-label-md text-on-surface">{{ rupiah($row['revenue']) }}</td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right text-on-surface-variant">{{ rupiah($row['commission']) }}</td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right text-on-surface">{{ rupiah($row['owner']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-md py-space-xl text-center text-on-surface-variant">Belum ada penjualan di periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Per afiliator --}}
        <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-5">
            <div class="p-space-md">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Penjualan per Afiliator</h2>
                <p class="font-caption text-caption text-on-surface-variant">Hanya order yang datang dari link referral</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-space-md py-3">Afiliator</th>
                            <th class="px-space-md py-3 text-right">Order</th>
                            <th class="px-space-md py-3 text-right">Omzet &amp; Komisi</th>
                        </tr>
                    </thead>
                    <tbody class="tabular divide-y divide-surface-container-low">
                        @forelse ($byAffiliate as $row)
                            <tr class="hover:bg-surface-container-low/60">
                                <td class="px-space-md py-3">
                                    <a href="{{ route('admin.affiliates.show', $row['affiliate']) }}" class="font-label-md text-label-md text-on-surface hover:text-primary hover:underline">{{ $row['affiliate']->name }}</a>
                                </td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right">{{ $row['orders'] }}</td>
                                <td class="whitespace-nowrap px-space-md py-3 text-right">
                                    <p class="font-label-md text-label-md text-on-surface">{{ rupiah($row['revenue']) }}</p>
                                    <p class="font-caption text-caption text-secondary">Komisi {{ rupiah($row['commission']) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-space-md py-space-xl text-center text-on-surface-variant">Belum ada penjualan lewat afiliator.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
