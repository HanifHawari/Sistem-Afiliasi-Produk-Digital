@php
    $conversion = $affiliate->referral_clicks_count ? $affiliate->sales_count / $affiliate->referral_clicks_count * 100 : 0;
    $balance = (int) $affiliate->earned - (int) $affiliate->withdrawn;
    $profile = $affiliate->affiliateProfile;
@endphp

<x-layouts.admin :title="$affiliate->name">
    <x-page-header eyebrow="Afiliator" :title="$affiliate->name" :description="'Bergabung '.$affiliate->created_at->translatedFormat('d F Y').' · '.$affiliate->email">
        <x-slot name="actions">
            <a href="{{ route('admin.affiliates.index') }}" class="flex items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-md py-2.5 text-on-surface shadow-sm hover:bg-surface-container">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">arrow_back</span>
                <span class="font-label-md text-label-md">Semua Afiliator</span>
            </a>
            <form method="POST" action="{{ route('admin.affiliates.toggle', $affiliate) }}"
                  onsubmit="return confirm({{ Js::from(($affiliate->is_active ? 'Nonaktifkan ' : 'Aktifkan ').$affiliate->name.'?') }})">
                @csrf @method('PATCH')
                <button @class([
                    'flex items-center gap-space-xs rounded-lg px-space-md py-2.5 shadow-sm transition-all',
                    'bg-error-container text-on-error-container hover:bg-error/20' => $affiliate->is_active,
                    'bg-secondary text-on-secondary hover:bg-secondary/90' => ! $affiliate->is_active,
                ])>
                    <span class="material-symbols-outlined text-[18px]">power_settings_new</span>
                    <span class="font-label-md text-label-md">{{ $affiliate->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}</span>
                </button>
            </form>
        </x-slot>
    </x-page-header>

    @unless ($affiliate->is_active)
        <div class="mb-space-lg flex items-center gap-space-sm rounded-xl bg-surface-container-highest p-space-md text-on-surface-variant">
            <span class="material-symbols-outlined">block</span>
            <span class="font-body-md text-body-md">Akun ini sedang <b>nonaktif</b>. Afiliator tidak bisa login sampai diaktifkan kembali.</span>
        </div>
    @endunless

    {{-- Statistik --}}
    <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
        <x-stat-card label="Total Klik Link" icon="ads_click" :value="number_format($affiliate->referral_clicks_count, 0, ',', '.')"
            :caption="$affiliate->referral_links_count.' link produk dibuat'" />
        <x-stat-card label="Penjualan" icon="shopping_bag" tone="tertiary" :value="$affiliate->sales_count.' order'"
            :caption="'Omzet '.rupiah($affiliate->revenue)">
            <span class="flex items-center gap-1.5 text-secondary">
                <span class="material-symbols-outlined text-[16px]">percent</span>
                Konversi {{ number_format($conversion, 1, ',', '.') }}%
            </span>
        </x-stat-card>
        <x-stat-card label="Total Komisi" icon="paid" tone="secondary" value-class="text-secondary" :value="rupiah($affiliate->earned)"
            :caption="'Sudah ditransfer '.rupiah($affiliate->paid_out)" />
        <x-stat-card label="Saldo Belum Dicairkan" icon="account_balance_wallet" highlight value-class="text-primary" :value="rupiah($balance)"
            caption="Komisi dikurangi pengajuan pencairan" />
    </div>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        <div class="flex flex-col gap-space-lg xl:col-span-8">
            {{-- Performa per produk --}}
            <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                <div class="p-space-md">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Performa per Produk</h2>
                    <p class="font-caption text-caption text-on-surface-variant">Link referral yang dibuat afiliator ini</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="ledger w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                                <th class="px-space-md py-3">Produk &amp; Kode</th>
                                <th class="px-space-md py-3 text-right">Klik</th>
                                <th class="px-space-md py-3 text-right">Terjual</th>
                                <th class="px-space-md py-3 text-right">Konversi</th>
                                <th class="px-space-md py-3 text-right">Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="tabular divide-y divide-surface-container-low">
                            @forelse ($links as $link)
                                <tr class="hover:bg-surface-container-low/60">
                                    <td class="px-space-md py-3">
                                        <p class="font-label-md text-label-md text-on-surface">{{ $link->product->name }}</p>
                                        <span class="rounded-full bg-secondary-fixed px-1.5 font-caption text-[10px] text-on-secondary-fixed">ref: {{ $link->code }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-space-md py-3 text-right">{{ number_format($link->clicks_count, 0, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-space-md py-3 text-right">{{ $link->sales_count }}</td>
                                    <td class="whitespace-nowrap px-space-md py-3 text-right">{{ $link->clicks_count ? number_format($link->sales_count / $link->clicks_count * 100, 1, ',', '.') : '0' }}%</td>
                                    <td class="whitespace-nowrap px-space-md py-3 text-right font-label-md text-label-md">{{ rupiah($link->revenue) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-space-md py-space-xl text-center text-on-surface-variant">Afiliator ini belum membuat link referral.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Riwayat komisi --}}
            <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                <div class="p-space-md">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Riwayat Komisi</h2>
                    <p class="font-caption text-caption text-on-surface-variant">10 komisi terakhir dari penjualan yang terverifikasi</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="ledger w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                                <th class="px-space-md py-3">Invoice &amp; Produk</th>
                                <th class="px-space-md py-3">Tanggal</th>
                                <th class="px-space-md py-3 text-right">Komisi</th>
                                <th class="px-space-md py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            @forelse ($commissions as $commission)
                                <tr class="hover:bg-surface-container-low/60">
                                    <td class="px-space-md py-3">
                                        <a href="{{ route('admin.orders.index', ['status' => 'all', 'order' => $commission->order_id]) }}" class="font-label-md text-label-md text-primary hover:underline">#{{ $commission->order->invoice_number }}</a>
                                        <p class="font-caption text-caption text-on-surface-variant">{{ $commission->order->product->name }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-space-md py-3 font-body-sm text-body-sm text-on-surface-variant">{{ $commission->created_at->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="tabular whitespace-nowrap px-space-md py-3 text-right">
                                        <p class="font-label-md text-label-md text-secondary">{{ rupiah($commission->amount) }}</p>
                                        <p class="font-caption text-caption text-on-surface-variant">{{ (float) $commission->rate }}% dari {{ rupiah($commission->order->amount) }}</p>
                                    </td>
                                    <td class="px-space-md py-3 text-center">
                                        <x-badge :tone="$commission->status->tone()">{{ $commission->status->label() }}</x-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-space-md py-space-xl text-center text-on-surface-variant">Belum ada komisi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="flex flex-col gap-space-md xl:col-span-4">
            {{-- Profil --}}
            <div class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <div class="flex items-center gap-space-sm">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-fixed font-headline-sm text-headline-sm text-on-primary-fixed-variant">{{ str($affiliate->name)->substr(0, 1)->upper() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-label-md text-label-md text-on-surface">{{ $affiliate->name }}</p>
                        <x-badge :tone="$affiliate->is_active ? 'success' : 'neutral'">{{ $affiliate->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                </div>
                <dl class="mt-1 flex flex-col gap-1 rounded-lg bg-surface-container-low p-space-sm font-body-sm text-body-sm">
                    <div class="flex justify-between gap-2"><dt class="text-on-surface-variant">Email</dt><dd class="truncate text-on-surface">{{ $affiliate->email }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-on-surface-variant">No. HP</dt><dd class="text-on-surface">{{ $affiliate->phone ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-on-surface-variant">Bergabung</dt><dd class="text-on-surface">{{ $affiliate->created_at->translatedFormat('d M Y') }}</dd></div>
                </dl>
            </div>

            {{-- Rekening --}}
            <div class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px] text-primary">account_balance</span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Rekening Pencairan</h3>
                </div>
                @if ($profile?->account_number)
                    <div class="flex items-center gap-2 rounded-lg bg-surface-container-low p-space-sm">
                        <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold text-primary">{{ $profile->bank_name }}</span>
                        <div>
                            <p class="tabular font-label-md text-label-md text-on-surface">{{ $profile->account_number }}</p>
                            <p class="font-caption text-caption text-on-surface-variant">a.n. {{ $profile->account_holder }}</p>
                        </div>
                    </div>
                @else
                    <p class="rounded-lg bg-surface-container-low p-space-sm font-body-sm text-body-sm text-on-surface-variant">Afiliator belum mengisi data rekening.</p>
                @endif
            </div>

            {{-- Riwayat pencairan --}}
            <div class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-secondary">payments</span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Riwayat Pencairan</h3>
                    </div>
                    <a href="{{ route('admin.withdrawals.index', ['status' => 'all', 'q' => $affiliate->email]) }}" class="font-label-sm text-label-sm text-primary hover:underline">Semua →</a>
                </div>
                <ul class="flex flex-col divide-y divide-surface-container-low">
                    @forelse ($withdrawals as $withdrawal)
                        <li class="flex items-center justify-between gap-2 py-2.5">
                            <div>
                                <a href="{{ route('admin.withdrawals.index', ['status' => 'all', 'withdrawal' => $withdrawal->id]) }}" class="tabular font-label-md text-label-md text-on-surface hover:text-primary">{{ rupiah($withdrawal->amount) }}</a>
                                <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                            <x-badge :tone="$withdrawal->status->tone()">{{ $withdrawal->status->label() }}</x-badge>
                        </li>
                    @empty
                        <li class="py-2.5 font-body-sm text-body-sm text-on-surface-variant">Belum pernah mengajukan pencairan.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
