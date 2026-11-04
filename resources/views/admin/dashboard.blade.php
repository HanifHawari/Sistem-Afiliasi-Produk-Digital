<x-layouts.admin title="Dashboard">
    <x-page-header eyebrow="Panel Admin" title="Halo, {{ str(Auth::user()->name)->before(' ') }}"
        description="Ringkasan penjualan, komisi, dan transaksi yang perlu tindakan hari ini.">
        @if ($stats['waiting_count'] > 0)
            <x-slot name="meta">{{ $stats['waiting_count'] }} pembayaran butuh verifikasi</x-slot>
        @endif
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-md py-2.5 text-on-surface shadow-sm transition-all hover:bg-surface-container">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">category</span>
                <span class="font-label-md text-label-md">Kelola Produk</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-space-xs rounded-lg bg-primary-container px-space-md py-2.5 text-on-primary shadow-sm transition-all hover:bg-primary">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                <span class="font-label-md text-label-md">Verifikasi Pembayaran</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- Statistik --}}
    <div class="mb-space-lg grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Penjualan Bulan Ini" icon="payments" :value="rupiah($stats['revenue_month'])"
            :caption="$stats['orders_month'].' pesanan berhasil'">
            <span class="flex items-center gap-1.5 text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                {{ now()->translatedFormat('F Y') }}
            </span>
        </x-stat-card>

        <x-stat-card label="Menunggu Verifikasi" icon="pending_actions" tone="tertiary"
            :value="$stats['waiting_count'].' pesanan'" caption="Bukti bayar sudah diunggah customer">
            @if ($stats['waiting_oldest'])
                <span class="flex items-center gap-1.5 text-error">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    Tertua {{ $stats['waiting_oldest']->diffForHumans() }}
                </span>
            @else
                <span class="flex items-center gap-1.5 text-secondary">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Tidak ada antrean
                </span>
            @endif
        </x-stat-card>

        <x-stat-card label="Komisi Terkredit Bulan Ini" icon="handshake" tone="secondary" value-class="text-secondary"
            :value="rupiah($stats['commission_month'])" :caption="$stats['affiliates'].' afiliator terdaftar'">
            <span class="flex items-center gap-1.5 text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                {{ $stats['active_products'] }} produk aktif
            </span>
        </x-stat-card>

        <x-stat-card label="Antrean Pencairan" icon="hourglass_top" highlight value-class="text-primary"
            :value="rupiah($stats['withdrawal_pending_sum'])" :caption="$stats['withdrawal_pending_count'].' permintaan butuh persetujuan'">
            @if ($stats['withdrawal_pending_count'] > 0)
                <x-badge tone="info" pulse class="bg-surface-container-lowest">Perlu Diproses</x-badge>
            @else
                <span class="text-on-surface-variant">Belum ada pengajuan</span>
            @endif
            <a href="{{ route('admin.withdrawals.index') }}" class="text-primary hover:underline">Tinjau →</a>
        </x-stat-card>
    </div>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Antrean verifikasi --}}
        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-8">
            <div class="flex items-center justify-between p-space-md">
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Pembayaran Menunggu Verifikasi</h2>
                    <p class="font-caption text-caption text-on-surface-variant">Diurutkan dari yang paling lama menunggu</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="font-label-sm text-label-sm text-primary hover:underline">Lihat semua →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-4 py-3">Transaksi</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Produk &amp; Afiliator</th>
                            <th class="px-4 py-3 text-right">Nominal</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse ($waitingOrders as $order)
                            <tr class="transition-colors hover:bg-surface-container-low">
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-label-md text-label-md text-primary">#{{ $order->invoice_number }}</span>
                                    <span class="block font-caption text-caption text-on-surface-variant">{{ $order->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-label-md text-label-md text-on-surface">{{ $order->customer->name }}</span>
                                    <span class="block font-caption text-caption text-on-surface-variant">{{ $order->customer->email }}</span>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="font-label-md text-label-md text-on-surface">{{ $order->product->name }}</span>
                                    <span class="block font-caption text-caption {{ $order->referralLink ? 'text-primary' : 'text-outline' }}">
                                        {{ $order->referralLink?->affiliate->name ?? 'Organik (tanpa afiliator)' }}
                                    </span>
                                </td>
                                <td class="tabular whitespace-nowrap px-4 py-3.5 text-right align-top font-label-md text-label-md">{{ rupiah($order->amount) }}</td>
                                <td class="px-4 py-3.5 text-right align-top">
                                    <a href="{{ route('admin.orders.index', ['order' => $order->id]) }}" class="inline-flex rounded-lg bg-surface-container-high p-1.5 text-on-surface-variant hover:bg-surface-container-highest" title="Periksa">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-space-xl text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined mb-1 block text-[32px] text-secondary">task_alt</span>
                                    Tidak ada pembayaran yang menunggu verifikasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-space-md xl:col-span-4">
            {{-- Produk terlaris --}}
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <div class="mb-space-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px] text-primary">trophy</span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Produk Terlaris</h3>
                </div>
                <ul class="flex flex-col divide-y divide-surface-container-low">
                    @forelse ($topProducts as $i => $product)
                        <li class="flex items-center gap-space-sm py-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-surface-container-low font-label-sm text-label-sm text-primary">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-label-md text-label-md text-on-surface">{{ $product->name }}</p>
                                <p class="font-caption text-caption text-on-surface-variant">{{ $product->sold_count }} terjual</p>
                            </div>
                            <span class="tabular whitespace-nowrap font-label-sm text-label-sm text-secondary">{{ rupiah($product->revenue) }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-on-surface-variant">Belum ada produk.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Antrean pencairan --}}
            <div class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <div class="mb-space-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-secondary">account_balance_wallet</span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Pengajuan Pencairan</h3>
                    </div>
                    <a href="{{ route('admin.withdrawals.index') }}" class="font-label-sm text-label-sm text-primary hover:underline">Semua →</a>
                </div>
                <ul class="flex flex-col divide-y divide-surface-container-low">
                    @forelse ($pendingWithdrawals as $withdrawal)
                        <li class="flex items-center justify-between gap-space-sm py-2.5">
                            <div class="min-w-0">
                                <p class="truncate font-label-md text-label-md text-on-surface">{{ $withdrawal->affiliate->name }}</p>
                                <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->bank_name }} · {{ $withdrawal->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="tabular whitespace-nowrap font-label-md text-label-md text-secondary">{{ rupiah($withdrawal->amount) }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-on-surface-variant">Belum ada pengajuan pencairan.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
