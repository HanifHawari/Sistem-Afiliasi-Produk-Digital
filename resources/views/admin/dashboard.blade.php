@php
    $revenueChange = $stats['revenue_last_month'] > 0
        ? ($stats['revenue_month'] - $stats['revenue_last_month']) / $stats['revenue_last_month'] * 100
        : ($stats['revenue_month'] > 0 ? 100 : null);
    $totalSoldMonth = max(1, $topProducts->sum('sold_count'));
@endphp

<x-layouts.admin title="Dashboard">
    <x-page-header :title="'Selamat Datang, '.str(Auth::user()->name)->before(' ')"
        description="Ringkasan penjualan, komisi, dan transaksi yang perlu tindakan hari ini.">
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="btn-outline">
                <span class="material-symbols-outlined icon-light text-[20px] text-slate-500">add</span>
                Kelola Produk
            </a>
            <a href="{{ route('admin.orders.index') }}" class="btn-primary">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                Verifikasi Pembayaran
            </a>
        </x-slot>
    </x-page-header>

    {{-- Statistik --}}
    <x-stat-grid>
        <x-stat-card label="Penjualan Bulan Ini" :value="rupiah($stats['revenue_month'])" :caption="$stats['orders_month'].' pesanan berhasil'">
            <span>{{ now()->translatedFormat('F Y') }}</span>
            @if ($revenueChange !== null)
                <span @class(['pill-on-blue', 'text-emerald-600' => $revenueChange >= 0, 'text-red-600' => $revenueChange < 0])>
                    {{ ($revenueChange >= 0 ? '+' : '').round($revenueChange) }}% vs lalu
                </span>
            @endif
        </x-stat-card>

        <x-stat-card label="Menunggu Verifikasi" :value="$stats['waiting_count'].' pesanan'" caption="Bukti bayar sudah diunggah customer">
            @if ($stats['waiting_oldest'])
                <span class="pill-on-blue text-amber-600">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    Tertua {{ $stats['waiting_oldest']->diffForHumans() }}
                </span>
            @else
                <span>Tidak ada antrean</span>
            @endif
        </x-stat-card>

        <x-stat-card label="Komisi Terkredit Bulan Ini" :value="rupiah($stats['commission_month'])" :caption="$stats['affiliates'].' afiliator terdaftar'">
            <span>{{ $stats['active_products'] }} produk aktif</span>
        </x-stat-card>

        <x-stat-card label="Antrean Pencairan" :value="rupiah($stats['withdrawal_pending_sum'])" :caption="$stats['withdrawal_pending_count'].' permintaan butuh persetujuan'">
            <span class="text-white/80">{{ $stats['withdrawal_pending_count'] ? 'Segera proses' : 'Tidak ada antrean' }}</span>
            <a href="{{ route('admin.withdrawals.index') }}" class="font-bold text-white hover:underline">Tinjau</a>
        </x-stat-card>
    </x-stat-grid>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Pembayaran menunggu verifikasi --}}
        <section class="card overflow-hidden 2xl:col-span-8">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Pembayaran Menunggu Verifikasi</h2>
                    <p class="card-subtitle">Diurutkan dari yang paling lama menunggu</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="card-link">Lihat semua</a>
            </div>
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>Customer</th>
                            <th>Produk &amp; Afiliator</th>
                            <th>Nominal</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($waitingOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.index', ['order' => $order->id]) }}" class="block font-bold text-blue-600 hover:underline">#{{ $order->invoice_number }}</a>
                                    <span class="mt-1 block text-[13px] text-slate-400">{{ $order->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $order->customer->name }}</span>
                                    <span class="mt-0.5 block text-[13px] text-slate-400">{{ $order->customer->email }}</span>
                                </td>
                                <td>
                                    <span class="block max-w-[13rem] truncate text-slate-800" title="{{ $order->product->name }}">{{ $order->product->name }}</span>
                                    @if ($order->referralLink)
                                        <span class="pill-blue mt-1.5 text-[13px] font-medium">{{ $order->referralLink->affiliate->name }}</span>
                                    @else
                                        <span class="mt-1.5 block text-[13px] text-slate-400">Tanpa afiliator</span>
                                    @endif
                                </td>
                                <td class="tabular whitespace-nowrap font-bold text-slate-900">{{ rupiah($order->amount) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.orders.index', ['order' => $order->id]) }}" class="btn-soft">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        Periksa
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-500">Tidak ada pembayaran yang menunggu verifikasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-col gap-6 2xl:col-span-4">
            {{-- Produk terlaris --}}
            <section class="card">
                <div class="card-header items-center">
                    <h2 class="card-title">Produk Terlaris</h2>
                    <span class="text-sm text-slate-400">Bulan Ini</span>
                </div>
                <ul class="card-body flex flex-col gap-5">
                    @forelse ($topProducts as $i => $product)
                        @php($share = $product->sold_count / $totalSoldMonth * 100)
                        <li class="flex gap-4">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-semibold',
                                'bg-blue-600 text-white' => $i === 0,
                                'border border-slate-200 bg-slate-50 text-slate-600' => $i !== 0,
                            ])>{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="truncate text-[15px] text-slate-800" title="{{ $product->name }}">{{ $product->name }}</p>
                                    <p @class(['tabular shrink-0 text-[15px] font-semibold', 'text-emerald-600' => $i === 0, 'text-slate-700' => $i !== 0])>{{ rupiah($product->revenue) }}</p>
                                </div>
                                <div class="mt-1 flex justify-between text-[13px] text-slate-400">
                                    <span>{{ $product->sold_count }} terjual</span>
                                    <span>{{ round($share) }}% vol</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $share }}%"></div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada penjualan bulan ini.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Pengajuan pencairan --}}
            <section class="card">
                <div class="card-header items-center">
                    <h2 class="card-title">Pengajuan Pencairan</h2>
                    <a href="{{ route('admin.withdrawals.index') }}" class="card-link">Semua</a>
                </div>
                <div class="card-body flex flex-col gap-3">
                    @forelse ($pendingWithdrawals as $withdrawal)
                        <div class="rounded-xl border border-slate-200/80 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-800">{{ $withdrawal->affiliate->name }}</p>
                                    <p class="mt-0.5 text-[13px] text-slate-400">{{ $withdrawal->bank_name }} &bull; {{ $withdrawal->created_at->diffForHumans() }}</p>
                                </div>
                                <p class="tabular shrink-0 font-semibold text-blue-600">{{ rupiah($withdrawal->amount) }}</p>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                <span class="pill-amber">Menunggu Persetujuan</span>
                                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">
                                    @csrf
                                    <button class="btn-primary btn-sm">Setujui</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada pengajuan pencairan.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
