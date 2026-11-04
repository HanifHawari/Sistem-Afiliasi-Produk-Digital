@php
    $tabs = ['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'];
@endphp

<x-layouts.admin title="Afiliator">
    <x-page-header eyebrow="Kelola" title="Afiliator"
        description="Pantau performa setiap afiliator: link, klik, penjualan, komisi, dan saldo yang belum dicairkan." />

    {{-- Statistik --}}
    <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
        <x-stat-card label="Total Afiliator" icon="group" :value="$stats['total'].' orang'">
            <span class="flex items-center gap-1.5 text-secondary">
                <span class="material-symbols-outlined text-[16px]">person_add</span>
                +{{ $stats['new_month'] }} bulan ini
            </span>
        </x-stat-card>
        <x-stat-card label="Afiliator Aktif" icon="how_to_reg" tone="secondary" :value="$stats['active'].' orang'"
            :caption="($stats['total'] - $stats['active']).' akun dinonaktifkan'" />
        <x-stat-card label="Sudah Menghasilkan Penjualan" icon="trending_up" tone="tertiary" :value="$stats['with_sales'].' orang'"
            :caption="$stats['total'] ? round($stats['with_sales'] / $stats['total'] * 100).'% dari semua afiliator' : 'Belum ada afiliator'" />
        <x-stat-card label="Total Komisi Afiliator" icon="paid" highlight value-class="text-primary" :value="rupiah($stats['commission_total'])"
            caption="Komisi dari pembayaran yang disetujui" />
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.affiliates.index') }}" class="mb-space-lg flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-surface-container-low p-1.5">
        <div class="no-scrollbar flex w-full items-center gap-1 overflow-x-auto sm:w-auto sm:flex-wrap">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.affiliates.index', array_filter(['status' => $key, 'sort' => $sort, 'q' => $search])) }}"
                   @if ($status === $key) x-data x-init="$el.parentElement.scrollLeft = $el.offsetLeft - 24" @endif
                   @class([
                       'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg px-space-md py-1.5 font-label-md text-label-md transition-colors',
                       'bg-surface-container-lowest font-semibold text-primary shadow-sm' => $status === $key,
                       'text-on-surface-variant hover:text-on-surface' => $status !== $key,
                   ])>
                    @if ($status === $key)
                        <span class="h-2 w-2 rounded-full bg-tertiary"></span>
                    @endif
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="flex w-full flex-wrap items-center gap-space-xs sm:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <select name="sort" onchange="this.form.submit()"
                    class="rounded-lg border-0 bg-surface-container-lowest py-1.5 pl-3 pr-8 font-body-sm text-body-sm text-on-surface focus:ring-2 focus:ring-primary-container">
                @foreach ($sorts as $key => $label)
                    <option value="{{ $key }}" @selected($sort === $key)>Urutkan: {{ $label }}</option>
                @endforeach
            </select>
            <div class="flex flex-1 items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-sm py-1">
                <span class="material-symbols-outlined text-[18px] text-outline">search</span>
                <input name="q" value="{{ $search }}" type="text" placeholder="Cari nama, email, no. HP..."
                       class="w-full border-0 bg-transparent p-1 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:ring-0 sm:w-56">
            </div>
        </div>
    </form>

    {{-- Tabel afiliator --}}
    <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
        <div class="overflow-x-auto">
            <table class="ledger w-full border-collapse text-left">
                <thead>
                    <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                        <th class="px-space-md py-3">Afiliator</th>
                        <th class="px-space-md py-3 text-right">Link &amp; Klik</th>
                        <th class="px-space-md py-3 text-right">Penjualan</th>
                        <th class="px-space-md py-3 text-right">Komisi &amp; Saldo</th>
                        <th class="px-space-md py-3 text-center">Status</th>
                        <th class="px-space-md py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-low">
                    @forelse ($affiliates as $affiliate)
                        @php
                            $conversion = $affiliate->referral_clicks_count ? $affiliate->sales_count / $affiliate->referral_clicks_count * 100 : 0;
                            $balance = (int) $affiliate->earned - (int) $affiliate->withdrawn;
                        @endphp
                        <tr @class(['transition-colors hover:bg-surface-container-low/60', 'opacity-60' => ! $affiliate->is_active])>
                            <td class="px-space-md py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-label-md text-label-md text-on-primary-fixed-variant">{{ str($affiliate->name)->substr(0, 1)->upper() }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="font-label-md text-label-md text-on-surface hover:text-primary hover:underline">{{ $affiliate->name }}</a>
                                        <p class="font-caption text-caption text-on-surface-variant">{{ $affiliate->email }}{{ $affiliate->phone ? ' · '.$affiliate->phone : '' }}</p>
                                        <p class="font-caption text-caption text-outline">Bergabung {{ $affiliate->created_at->translatedFormat('d M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="tabular whitespace-nowrap px-space-md py-3 text-right">
                                <p class="font-label-md text-label-md text-on-surface">{{ number_format($affiliate->referral_clicks_count, 0, ',', '.') }} klik</p>
                                <p class="font-caption text-caption text-on-surface-variant">{{ $affiliate->referral_links_count }} link produk</p>
                            </td>
                            <td class="tabular whitespace-nowrap px-space-md py-3 text-right">
                                <p class="font-label-md text-label-md text-on-surface">{{ $affiliate->sales_count }} terjual</p>
                                <p class="font-caption text-caption text-on-surface-variant">{{ rupiah($affiliate->revenue) }} · konversi {{ number_format($conversion, 1, ',', '.') }}%</p>
                            </td>
                            <td class="tabular whitespace-nowrap px-space-md py-3 text-right">
                                <p class="font-label-md text-label-md font-bold text-secondary">{{ rupiah($affiliate->earned) }}</p>
                                <p class="font-caption text-caption text-on-surface-variant">Saldo {{ rupiah($balance) }}</p>
                            </td>
                            <td class="px-space-md py-3 text-center">
                                <x-badge :tone="$affiliate->is_active ? 'success' : 'neutral'">{{ $affiliate->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </td>
                            <td class="px-space-md py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="rounded-lg bg-surface-container-high p-1.5 text-on-surface-variant transition-colors hover:bg-surface-container-highest" title="Lihat detail">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.affiliates.toggle', $affiliate) }}"
                                          onsubmit="return confirm({{ Js::from(($affiliate->is_active ? 'Nonaktifkan ' : 'Aktifkan ').$affiliate->name.'?') }})">
                                        @csrf @method('PATCH')
                                        <button title="{{ $affiliate->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                @class(['rounded-lg p-1.5 transition-colors', 'text-error hover:bg-error-container' => $affiliate->is_active, 'text-secondary hover:bg-secondary-fixed' => ! $affiliate->is_active])>
                                            <span class="material-symbols-outlined text-[18px]">power_settings_new</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-space-md py-space-2xl text-center text-on-surface-variant">
                                <span class="material-symbols-outlined mb-1 block text-[36px] text-outline">group</span>
                                {{ $search ? 'Afiliator "'.$search.'" tidak ditemukan.' : 'Belum ada afiliator terdaftar.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between bg-surface-container-low px-space-md py-3">
            <span class="font-caption text-caption text-on-surface-variant">
                Menampilkan {{ $affiliates->firstItem() ?? 0 }}-{{ $affiliates->lastItem() ?? 0 }} dari {{ $affiliates->total() }} afiliator
            </span>
            <div class="flex items-center gap-1">
                <a href="{{ $affiliates->previousPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => $affiliates->onFirstPage()])>
                    <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                </a>
                <span class="px-2 font-label-sm text-label-sm text-on-surface">Hal {{ $affiliates->currentPage() }} dari {{ $affiliates->lastPage() }}</span>
                <a href="{{ $affiliates->nextPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => ! $affiliates->hasMorePages()])>
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.admin>
