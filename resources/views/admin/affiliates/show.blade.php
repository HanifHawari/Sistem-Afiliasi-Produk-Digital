@php
    $conversion = $affiliate->referral_clicks_count ? $affiliate->sales_count / $affiliate->referral_clicks_count * 100 : 0;
    $balance = (int) $affiliate->earned - (int) $affiliate->withdrawn;
    $profile = $affiliate->affiliateProfile;
@endphp

<x-layouts.admin :title="$affiliate->name">
    <x-page-header :title="$affiliate->name" :description="'Bergabung '.$affiliate->created_at->translatedFormat('d F Y').' • '.$affiliate->email">
        <x-slot name="actions">
            <a href="{{ route('admin.affiliates.index') }}" class="btn-outline">Semua Afiliator</a>
            <form method="POST" action="{{ route('admin.affiliates.toggle', $affiliate) }}"
                  onsubmit="return confirm({{ Js::from(($affiliate->is_active ? 'Nonaktifkan ' : 'Aktifkan ').$affiliate->name.'?') }})">
                @csrf @method('PATCH')
                <button @class(['btn', 'bg-red-50 text-red-600 hover:bg-red-100' => $affiliate->is_active, 'btn-success' => ! $affiliate->is_active])>
                    {{ $affiliate->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                </button>
            </form>
        </x-slot>
    </x-page-header>

    @unless ($affiliate->is_active)
        <div class="mb-8 rounded-2xl border border-slate-200 bg-slate-100 px-6 py-4 text-slate-600">
            Akun ini sedang <b>nonaktif</b>. Afiliator tidak bisa login sampai diaktifkan kembali.
        </div>
    @endunless

    {{-- Statistik --}}
    <x-stat-grid>
        <x-stat-card label="Total Klik Link" :value="number_format($affiliate->referral_clicks_count, 0, ',', '.')" :caption="$affiliate->referral_links_count.' link produk dibuat'" />
        <x-stat-card label="Penjualan" :value="$affiliate->sales_count.' order'" :caption="'Omzet '.rupiah($affiliate->revenue)">
            <span>Konversi {{ number_format($conversion, 1, ',', '.') }}%</span>
        </x-stat-card>
        <x-stat-card label="Total Komisi" :value="rupiah($affiliate->earned)" :caption="'Sudah ditransfer '.rupiah($affiliate->paid_out)" />
        <x-stat-card label="Saldo Belum Dicairkan" :value="rupiah($balance)" caption="Komisi dikurangi pengajuan pencairan" />
    </x-stat-grid>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        <div class="flex flex-col gap-6 2xl:col-span-8">
            {{-- Performa per produk --}}
            <section class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Performa per Produk</h2>
                        <p class="card-subtitle">Link referral yang dibuat afiliator ini</p>
                    </div>
                </div>
                <div class="relative overflow-x-auto">
                    <table class="data-table ledger">
                        <thead>
                            <tr>
                                <th>Produk &amp; Kode</th>
                                <th>Klik</th>
                                <th>Terjual</th>
                                <th>Konversi</th>
                                <th>Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="tabular">
                            @forelse ($links as $link)
                                <tr>
                                    <td>
                                        <span class="block font-medium text-slate-800">{{ $link->product->name }}</span>
                                        <span class="pill-blue mt-1.5">ref: {{ $link->code }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">{{ number_format($link->clicks_count, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap">{{ $link->sales_count }}</td>
                                    <td class="whitespace-nowrap">{{ $link->clicks_count ? number_format($link->sales_count / $link->clicks_count * 100, 1, ',', '.') : '0' }}%</td>
                                    <td class="whitespace-nowrap font-bold text-slate-900">{{ rupiah($link->revenue) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-500">Afiliator ini belum membuat link referral.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Riwayat komisi --}}
            <section class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Riwayat Komisi</h2>
                        <p class="card-subtitle">10 komisi terakhir dari penjualan terverifikasi</p>
                    </div>
                </div>
                <div class="relative overflow-x-auto">
                    <table class="data-table ledger">
                        <thead>
                            <tr>
                                <th>Invoice &amp; Produk</th>
                                <th>Tanggal</th>
                                <th>Komisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($commissions as $commission)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.index', ['status' => 'all', 'order' => $commission->order_id]) }}" class="block font-bold text-blue-600 hover:underline">#{{ $commission->order->invoice_number }}</a>
                                        <span class="block text-[13px] text-slate-400">{{ $commission->order->product->name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $commission->created_at->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="tabular whitespace-nowrap">
                                        <span class="block font-bold text-emerald-600">{{ rupiah($commission->amount) }}</span>
                                        <span class="block text-[13px] text-slate-400">{{ (float) $commission->rate }}% dari {{ rupiah($commission->order->amount) }}</span>
                                    </td>
                                    <td><x-badge :tone="$commission->status->tone()">{{ $commission->status->label() }}</x-badge></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-500">Belum ada komisi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="flex flex-col gap-6 2xl:col-span-4">
            {{-- Profil --}}
            <section class="card">
                <div class="card-header items-center">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-avatar :user="$affiliate" class="h-12 w-12 text-lg" />
                        <h2 class="card-title truncate">{{ $affiliate->name }}</h2>
                    </div>
                    <x-badge :tone="$affiliate->is_active ? 'success' : 'neutral'">{{ $affiliate->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                </div>
                <dl class="card-body grid gap-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="truncate text-slate-800">{{ $affiliate->email }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">No. HP</dt><dd class="text-slate-800">{{ $affiliate->phone ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Bergabung</dt><dd class="text-slate-800">{{ $affiliate->created_at->translatedFormat('d M Y') }}</dd></div>
                </dl>
            </section>

            {{-- Rekening --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Rekening Pencairan</h2>
                </div>
                <div class="card-body">
                    @if ($profile?->account_number)
                        <div class="rounded-xl border border-slate-200/80 px-4 py-3">
                            <p class="tabular font-semibold text-slate-800">{{ $profile->bank_name }} &bull; {{ $profile->account_number }}</p>
                            <p class="text-[13px] text-slate-400">a.n. {{ $profile->account_holder }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Afiliator belum mengisi data rekening.</p>
                    @endif
                </div>
            </section>

            {{-- Riwayat pencairan --}}
            <section class="card">
                <div class="card-header items-center">
                    <h2 class="card-title">Riwayat Pencairan</h2>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'all', 'q' => $affiliate->email]) }}" class="card-link">Semua</a>
                </div>
                <ul class="card-body flex flex-col divide-y divide-slate-100">
                    @forelse ($withdrawals as $withdrawal)
                        <li class="flex items-center justify-between gap-3 py-3 first:pt-0">
                            <div>
                                <a href="{{ route('admin.withdrawals.index', ['status' => 'all', 'withdrawal' => $withdrawal->id]) }}" class="tabular font-semibold text-slate-800 hover:text-blue-600">{{ rupiah($withdrawal->amount) }}</a>
                                <p class="text-[13px] text-slate-400">{{ $withdrawal->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                            <x-badge :tone="$withdrawal->status->tone()">{{ $withdrawal->status->label() }}</x-badge>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum pernah mengajukan pencairan.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-layouts.admin>
