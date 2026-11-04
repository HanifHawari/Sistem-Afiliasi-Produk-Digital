@use('App\Enums\OrderStatus')

@php
    $tabs = [
        'all' => ['Semua', $counts->sum()],
        OrderStatus::WaitingVerification->value => ['Menunggu Verifikasi', $counts[OrderStatus::WaitingVerification->value] ?? 0],
        OrderStatus::Pending->value => ['Belum Bayar', $counts[OrderStatus::Pending->value] ?? 0],
        OrderStatus::Paid->value => ['Berhasil', $counts[OrderStatus::Paid->value] ?? 0],
        OrderStatus::Rejected->value => ['Ditolak', $counts[OrderStatus::Rejected->value] ?? 0],
    ];

    $filterItems = collect($tabs)->map(fn ($tab, $key) => [
        'label' => $tab[0],
        'count' => $tab[1],
        'href' => route('admin.orders.index', array_filter(['status' => $key, 'q' => $search])),
        'active' => $status === $key,
    ])->values()->all();
@endphp

<x-layouts.admin title="Verifikasi Pembayaran">
    <x-page-header title="Verifikasi Pembayaran"
        description="Tinjau bukti pembayaran transfer/QRIS, lalu buka akses produk digital untuk customer.">
        <x-slot name="actions">
            <a href="{{ route('admin.payment-accounts.index') }}" class="btn-outline">Rekening &amp; QRIS</a>
        </x-slot>
    </x-page-header>

    {{-- Statistik --}}
    <x-stat-grid>
        <x-stat-card label="Terverifikasi Hari Ini" :value="rupiah($stats['volume_today'])" caption="Total pembayaran disetujui hari ini" />
        <x-stat-card label="Menunggu Verifikasi" :value="$stats['waiting_count'].' pesanan'" caption="Bukti bayar sudah diunggah customer">
            @if ($stats['waiting_oldest'])
                <span class="pill-on-blue text-amber-600">Tertua {{ $stats['waiting_oldest']->diffForHumans() }}</span>
            @else
                <span>Tidak ada antrean</span>
            @endif
        </x-stat-card>
        <x-stat-card label="Komisi Terkredit Bulan Ini" :value="rupiah($stats['commission_month'])" caption="Otomatis saat pembayaran disetujui" />
        <x-stat-card label="Transaksi Berhasil" :value="$stats['paid_count'].' pesanan'" caption="Akses produk sudah terbuka" />
    </x-stat-grid>

    {{-- Tab status + pencarian --}}
    <div class="toolbar">
        <x-filter-menu :items="$filterItems" />
        <form method="GET" action="{{ route('admin.orders.index') }}" class="search">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined">search</span>
            <input name="q" value="{{ $search }}" type="text" placeholder="Cari invoice, pembeli, kode ref...">
        </form>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Tabel transaksi --}}
        <section class="card overflow-hidden 2xl:col-span-8">
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>Transaksi</th>
                            <th>Customer &amp; Produk</th>
                            <th>Nominal</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $isSelected = $selected?->is($order);
                                $rate = $order->commission?->rate ?? $order->product->commission_rate;
                                $commissionAmount = $order->referralLink ? ($order->commission?->amount ?? (int) round($order->amount * $rate / 100)) : 0;
                                $selectUrl = route('admin.orders.index', array_filter(['status' => $status, 'q' => $search, 'page' => $orders->currentPage(), 'order' => $order->id]));
                                $canVerify = in_array($order->status, [OrderStatus::Pending, OrderStatus::WaitingVerification]);
                            @endphp
                            <tr @class(['!bg-blue-50/60' => $isSelected])>
                                <td>
                                    <a href="{{ $selectUrl }}" class="block font-bold text-blue-600 hover:underline">#{{ $order->invoice_number }}</a>
                                    <span class="mt-1 block text-[13px] text-slate-400">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    <x-badge :tone="$order->status->tone()" class="mt-2">{{ $order->status->label() }}</x-badge>
                                </td>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $order->customer->name }}</span>
                                    <span class="block text-[13px] text-slate-400">{{ $order->customer->email }}</span>
                                    <span class="mt-2 block max-w-[16rem] truncate text-slate-700" title="{{ $order->product->name }}">{{ $order->product->name }}</span>
                                    @if ($order->referralLink)
                                        <span class="pill-blue mt-1.5 text-[13px] font-medium">{{ $order->referralLink->affiliate->name }}</span>
                                    @else
                                        <span class="mt-1 block text-[13px] text-slate-400">Tanpa afiliator</span>
                                    @endif
                                </td>
                                <td class="tabular whitespace-nowrap">
                                    <span class="block font-bold text-slate-900">{{ rupiah($order->amount) }}</span>
                                    <span class="mt-1 block text-[13px] text-slate-400">{{ $order->paymentAccount?->bank_name ?? $order->payment_method->label() }}</span>
                                    @if ($commissionAmount)
                                        <span class="block text-[13px] text-emerald-600">Komisi {{ rupiah($commissionAmount) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        @if ($canVerify)
                                            <form method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm({{ Js::from('Setujui pembayaran '.$order->invoice_number.'?') }})">
                                                @csrf
                                                <button class="btn-success btn-sm">Setujui</button>
                                            </form>
                                        @endif
                                        <a href="{{ $selectUrl }}" class="btn-soft">Periksa</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-500">Tidak ada transaksi{{ $search ? ' untuk pencarian "'.$search.'"' : '' }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 sm:px-7">
                <span class="text-sm text-slate-500">{{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} transaksi</span>
                <div class="flex items-center gap-2">
                    <a href="{{ $orders->previousPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => $orders->onFirstPage()])>Sebelumnya</a>
                    <a href="{{ $orders->nextPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => ! $orders->hasMorePages()])>Berikutnya</a>
                </div>
            </div>
        </section>

        {{-- Panel inspeksi --}}
        <div class="2xl:col-span-4">
            @if ($selected)
                @php
                    $canVerify = in_array($selected->status, [OrderStatus::Pending, OrderStatus::WaitingVerification]);
                    $rate = $selected->commission?->rate ?? $selected->product->commission_rate;
                    $commissionAmount = $selected->referralLink ? ($selected->commission?->amount ?? (int) round($selected->amount * $rate / 100)) : 0;
                    $proofIsImage = $selected->payment_proof && in_array(strtolower(pathinfo($selected->payment_proof, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                @endphp
                <section id="inspeksi" class="card scroll-mt-24"
                         x-init="if (new URLSearchParams(location.search).has('order') && innerWidth < 1536) $el.scrollIntoView()"
                         x-data="{ rejecting: {{ $errors->has('rejection_note') ? 'true' : 'false' }} }">
                    <div class="card-header items-center">
                        <div class="min-w-0">
                            <h2 class="card-title truncate">#{{ $selected->invoice_number }}</h2>
                            <p class="card-subtitle">{{ $selected->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                        </div>
                        <x-badge :tone="$selected->status->tone()">{{ $selected->status->label() }}</x-badge>
                    </div>

                    <div class="card-body flex flex-col gap-5">
                        <dl class="panel grid gap-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Total tagihan</dt><dd class="tabular font-bold text-blue-600">{{ rupiah($selected->amount) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Customer</dt><dd class="text-right text-slate-800">{{ $selected->customer->name }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Metode</dt><dd class="text-slate-800">{{ $selected->payment_method->label() }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">Tujuan</dt><dd class="text-right text-slate-800">{{ $selected->paymentAccount?->label() ?? '-' }}</dd></div>
                        </dl>

                        {{-- Bukti bayar --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-800">Bukti Pembayaran</span>
                                @if ($selected->payment_proof)
                                    <a href="{{ route('admin.orders.proof', $selected) }}" target="_blank" class="card-link">Perbesar</a>
                                @endif
                            </div>
                            <div class="flex min-h-[200px] items-center justify-center overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50">
                                @if ($proofIsImage)
                                    <img src="{{ route('admin.orders.proof', $selected) }}" alt="Bukti pembayaran {{ $selected->invoice_number }}" class="max-h-[360px] w-full object-contain">
                                @elseif ($selected->payment_proof)
                                    <a href="{{ route('admin.orders.proof', $selected) }}" target="_blank" class="card-link">Buka file bukti bayar</a>
                                @else
                                    <p class="px-5 text-center text-sm text-slate-400">Customer belum mengunggah bukti pembayaran.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Pembagian dana --}}
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-800">Pembagian Dana</p>
                            <dl class="panel tabular grid gap-2 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Dana masuk</dt><dd class="font-semibold text-slate-800">{{ rupiah($selected->amount) }}</dd></div>
                                @if ($selected->referralLink)
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Komisi {{ $selected->referralLink->affiliate->name }} ({{ (float) $rate }}%)</dt><dd class="font-semibold text-emerald-600">{{ rupiah($commissionAmount) }}</dd></div>
                                @endif
                                <div class="flex justify-between border-t border-slate-200 pt-2"><dt class="font-medium text-slate-800">Bagian owner</dt><dd class="font-bold text-blue-600">{{ rupiah($selected->amount - $commissionAmount) }}</dd></div>
                            </dl>
                        </div>

                        {{-- Aksi --}}
                        @if ($canVerify)
                            <div class="flex flex-col gap-2">
                                <form method="POST" action="{{ route('admin.orders.approve', $selected) }}">
                                    @csrf
                                    <button class="btn-success w-full">Verifikasi &amp; Buka Akses Produk</button>
                                </form>

                                <button type="button" x-show="!rejecting" @click="rejecting = true" class="btn-danger w-full">Tolak Transaksi</button>

                                <form method="POST" action="{{ route('admin.orders.reject', $selected) }}" x-show="rejecting" x-cloak class="flex flex-col gap-2 rounded-xl border border-red-100 bg-red-50/50 p-4">
                                    @csrf
                                    <label for="rejection_note" class="text-sm font-semibold text-red-700">Alasan penolakan (terlihat oleh customer)</label>
                                    <textarea id="rejection_note" name="rejection_note" rows="3" placeholder="Contoh: nominal transfer tidak sesuai tagihan" class="input">{{ old('rejection_note') }}</textarea>
                                    <x-input-error :messages="$errors->get('rejection_note')" />
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="rejecting = false" class="btn-outline btn-sm">Batal</button>
                                        <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700">Tolak Pembayaran</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div @class(['rounded-xl p-4 text-sm', 'bg-emerald-50 text-emerald-700' => $selected->isPaid(), 'bg-red-50 text-red-700' => ! $selected->isPaid()])>
                                {{ $selected->isPaid() ? 'Diverifikasi' : 'Ditolak' }} oleh {{ $selected->verifier?->name ?? '-' }}
                                @if ($selected->verified_at)
                                    &bull; {{ $selected->verified_at->translatedFormat('d M Y, H:i') }} WIB
                                @endif
                                @if ($selected->rejection_note)
                                    <p class="mt-1 text-[13px]">Alasan: {{ $selected->rejection_note }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            @else
                <section class="card px-7 py-12 text-center">
                    <p class="font-semibold text-slate-800">Pilih transaksi</p>
                    <p class="mt-1 text-sm text-slate-500">Klik "Periksa" pada tabel untuk melihat bukti bayar dan memverifikasi.</p>
                </section>
            @endif
        </div>
    </div>
</x-layouts.admin>
