@php
    $tabs = ['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'];
@endphp

<x-layouts.admin title="Afiliator">
    <x-page-header title="Afiliator"
        description="Pantau performa setiap afiliator: link, klik, penjualan, komisi, dan saldo." />

    {{-- Statistik --}}
    <div class="mb-8 grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-4">
        <x-stat-card label="Total Afiliator" :value="$stats['total'].' orang'" caption="Terdaftar di platform">
            <span>+{{ $stats['new_month'] }} bulan ini</span>
        </x-stat-card>
        <x-stat-card label="Afiliator Aktif" :value="$stats['active'].' orang'" :caption="($stats['total'] - $stats['active']).' akun dinonaktifkan'" />
        <x-stat-card label="Sudah Menjual" :value="$stats['with_sales'].' orang'"
            :caption="$stats['total'] ? round($stats['with_sales'] / $stats['total'] * 100).'% dari semua afiliator' : 'Belum ada afiliator'" />
        <x-stat-card label="Total Komisi Afiliator" :value="rupiah($stats['commission_total'])" caption="Dari pembayaran yang disetujui" />
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.affiliates.index') }}" class="toolbar">
        <div class="tabs">
            @foreach ($tabs as $key => $label)
                <x-tab-link :href="route('admin.affiliates.index', array_filter(['status' => $key, 'sort' => $sort, 'q' => $search]))" :active="$status === $key">{{ $label }}</x-tab-link>
            @endforeach
        </div>
        <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <select name="sort" onchange="this.form.submit()" class="input sm:w-56">
                @foreach ($sorts as $key => $label)
                    <option value="{{ $key }}" @selected($sort === $key)>Urutkan: {{ $label }}</option>
                @endforeach
            </select>
            <div class="search">
                <span class="material-symbols-outlined">search</span>
                <input name="q" value="{{ $search }}" type="text" placeholder="Cari nama, email, no. HP...">
            </div>
        </div>
    </form>

    {{-- Tabel afiliator --}}
    <section class="card overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="data-table ledger">
                <thead>
                    <tr>
                        <th>Afiliator</th>
                        <th>Klik</th>
                        <th>Penjualan</th>
                        <th>Komisi &amp; Saldo</th>
                        <th>Status</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($affiliates as $affiliate)
                        @php
                            $conversion = $affiliate->referral_clicks_count ? $affiliate->sales_count / $affiliate->referral_clicks_count * 100 : 0;
                            $balance = (int) $affiliate->earned - (int) $affiliate->withdrawn;
                        @endphp
                        <tr @class(['opacity-60' => ! $affiliate->is_active])>
                            <td>
                                <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="block font-semibold text-slate-800 hover:text-blue-600">{{ $affiliate->name }}</a>
                                <span class="block text-[13px] text-slate-400">{{ $affiliate->email }}{{ $affiliate->phone ? ' • '.$affiliate->phone : '' }}</span>
                            </td>
                            <td class="tabular whitespace-nowrap">
                                <span class="block font-semibold text-slate-800">{{ number_format($affiliate->referral_clicks_count, 0, ',', '.') }} klik</span>
                                <span class="block text-[13px] text-slate-400">{{ $affiliate->referral_links_count }} link produk</span>
                            </td>
                            <td class="tabular">
                                <span class="block font-semibold text-slate-800">{{ $affiliate->sales_count }} terjual</span>
                                <span class="block text-[13px] text-slate-400">{{ rupiah($affiliate->revenue) }} &bull; konversi {{ number_format($conversion, 1, ',', '.') }}%</span>
                            </td>
                            <td class="tabular whitespace-nowrap">
                                <span class="block font-bold text-emerald-600">{{ rupiah($affiliate->earned) }}</span>
                                <span class="block text-[13px] text-slate-400">Saldo {{ rupiah($balance) }}</span>
                            </td>
                            <td><x-badge :tone="$affiliate->is_active ? 'success' : 'neutral'">{{ $affiliate->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.affiliates.show', $affiliate) }}" class="btn-soft">Detail</a>
                                    <form method="POST" action="{{ route('admin.affiliates.toggle', $affiliate) }}"
                                          onsubmit="return confirm({{ Js::from(($affiliate->is_active ? 'Nonaktifkan ' : 'Aktifkan ').$affiliate->name.'?') }})">
                                        @csrf @method('PATCH')
                                        <button @class(['btn btn-sm', 'bg-red-50 text-red-600 hover:bg-red-100' => $affiliate->is_active, 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' => ! $affiliate->is_active])>
                                            {{ $affiliate->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                {{ $search ? 'Afiliator "'.$search.'" tidak ditemukan.' : 'Belum ada afiliator terdaftar.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 sm:px-7">
            <span class="text-sm text-slate-500">{{ $affiliates->firstItem() ?? 0 }}-{{ $affiliates->lastItem() ?? 0 }} dari {{ $affiliates->total() }} afiliator</span>
            <div class="flex items-center gap-2">
                <a href="{{ $affiliates->previousPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => $affiliates->onFirstPage()])>Sebelumnya</a>
                <a href="{{ $affiliates->nextPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => ! $affiliates->hasMorePages()])>Berikutnya</a>
            </div>
        </div>
    </section>
</x-layouts.admin>
