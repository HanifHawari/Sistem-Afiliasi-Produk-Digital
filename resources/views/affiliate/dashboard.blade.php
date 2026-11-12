<x-layouts.affiliate title="Ringkasan Afiliasi">

    {{-- ================================================================
         PAGE HEADER — sapaan + aksi navigasi cepat
         ================================================================ --}}
    <x-page-header
        :title="'Halo, '.str(Auth::user()->name)->before(' ')"
        description="Pantau performa link, penjualan, dan komisi Anda.">
        <x-slot name="actions">
            <a href="{{ route('affiliate.links.index') }}" class="btn-outline">
                Link & Kampanye
            </a>
            <a href="{{ route('affiliate.withdrawals.index') }}" class="btn-primary">
                Tarik Komisi
            </a>
        </x-slot>
    </x-page-header>

    {{-- ================================================================
         KARTU STATISTIK — 4 KPI: Klik, Penjualan, Saldo, Total Komisi
         ================================================================ --}}
    <x-stat-grid>
        {{-- KPI 1: Total Klik Link --}}
        <x-stat-card
            label="Total Klik Link"
            :value="number_format($totalClicks, 0, ',', '.')"
            caption="Dari semua link referral Anda." />

        {{-- KPI 2: Penjualan Berhasil --}}
        <x-stat-card
            label="Penjualan Berhasil"
            :value="$totalSales.' order'"
            caption="Order terverifikasi." />

        {{-- KPI 3: Saldo Siap Cair --}}
        <x-stat-card
            label="Saldo Siap Cair"
            :value="rupiah($balance)"
            caption="Komisi dikurangi pencairan.">
            <span>Bisa diajukan</span>
            <a href="{{ route('affiliate.withdrawals.index') }}" class="font-bold text-white hover:underline">Tarik</a>
        </x-stat-card>

        {{-- KPI 4: Total Komisi --}}
        <x-stat-card
            label="Total Komisi"
            :value="rupiah($totalCommission)"
            caption="Komisi yang sudah disetujui." />
    </x-stat-grid>

    {{-- ================================================================
         RIWAYAT PENJUALAN & KOMISI
         ================================================================ --}}
    <section class="card overflow-hidden">

        {{-- Header tabel --}}
        <div class="card-header">
            <div>
                <h2 class="card-title">Riwayat Penjualan & Komisi Masuk</h2>
                <p class="card-subtitle">5 transaksi referral terbaru yang sudah berhasil.</p>
            </div>
            <a href="{{ route('affiliate.links.index') }}" class="card-link">
                Lihat Link & Kampanye
            </a>
        </div>

        @if ($recentSales->isEmpty())
            {{-- ============================================================
                 EMPTY STATE — ditampilkan jika belum ada penjualan
                 ============================================================ --}}
            <div class="flex flex-col items-center gap-4 px-6 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <span class="material-symbols-outlined text-[36px]">receipt_long</span>
                </div>
                <div>
                    <p class="text-base font-semibold text-slate-700">Belum Ada Transaksi</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-400">
                        Bagikan link afiliasi Anda dan mulai promosikan produk digital agar komisi pertama Anda segera masuk.
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('affiliate.links.index') }}" class="btn-primary text-sm">
                        <span class="material-symbols-outlined text-[16px]">add_link</span>
                        Buat Link Afiliasi
                    </a>
                    <a href="{{ route('affiliate.products.index') }}" class="btn-outline text-sm">
                        Lihat Produk
                    </a>
                </div>
            </div>

        @else
            {{-- ============================================================
                 TABEL TRANSAKSI — responsive, overflow-x-auto
                 ============================================================ --}}
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Pembeli</th>
                            <th>Produk</th>
                            <th>Tanggal</th>
                            <th>Nominal Transaksi</th>
                            <th>Komisi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $order)
                            <tr>
                                {{-- Nomor Invoice --}}
                                <td class="whitespace-nowrap font-mono font-semibold text-blue-600">
                                    #{{ $order->invoice_number ?? $order->id }}
                                </td>

                                {{-- Nama Pembeli --}}
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $order->customer?->name ?? '—' }}</span>
                                </td>

                                {{-- Nama Produk --}}
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                                        <span class="line-clamp-1 max-w-[200px]">{{ $order->product?->name ?? '—' }}</span>
                                    </div>
                                </td>

                                {{-- Tanggal Transaksi --}}
                                <td class="whitespace-nowrap text-slate-400">
                                    {{ $order->created_at->translatedFormat('d M Y, H:i') }} WIB
                                </td>

                                {{-- Nominal Transaksi --}}
                                <td class="tabular whitespace-nowrap text-right font-semibold text-slate-800">
                                    Rp {{ number_format($order->amount, 0, ',', '.') }}
                                </td>

                                {{-- Nominal Komisi --}}
                                <td class="tabular whitespace-nowrap text-right font-bold text-emerald-600">
                                    @if ($order->commission)
                                        +Rp {{ number_format($order->commission->amount, 0, ',', '.') }}
                                    @else
                                        <span class="font-normal text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Status Transaksi --}}
                                <td class="text-center">
                                    <x-badge :tone="$order->status->tone()">
                                        {{ $order->status->label() }}
                                    </x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer tabel --}}
            <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                Menampilkan {{ $recentSales->count() }} transaksi terakhir yang sudah Paid.
            </div>
        @endif

    </section>

</x-layouts.affiliate>
