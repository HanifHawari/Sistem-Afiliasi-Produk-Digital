@use('App\Enums\WithdrawalStatus')

@php
    $tabs = [
        WithdrawalStatus::Pending->value => ['Diajukan', $counts[WithdrawalStatus::Pending->value] ?? 0],
        WithdrawalStatus::Approved->value => ['Siap Ditransfer', $counts[WithdrawalStatus::Approved->value] ?? 0],
        WithdrawalStatus::Paid->value => ['Sudah Ditransfer', $counts[WithdrawalStatus::Paid->value] ?? 0],
        WithdrawalStatus::Rejected->value => ['Ditolak', $counts[WithdrawalStatus::Rejected->value] ?? 0],
        'all' => ['Semua', $counts->sum()],
    ];
@endphp

<x-layouts.admin title="Pencairan Komisi">
    <x-page-header title="Pencairan Komisi"
        description="Periksa saldo afiliator, setujui pengajuan, lalu tandai lunas setelah dana ditransfer." />

    {{-- Statistik --}}
    <div class="mb-8 grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-4">
        <x-stat-card label="Pengajuan Baru" :value="rupiah($stats['pending_sum'])" :caption="$stats['pending_count'].' pengajuan menunggu persetujuan'">
            @if ($stats['pending_oldest'])
                <span class="pill-on-blue text-amber-600">Tertua {{ $stats['pending_oldest']->diffForHumans() }}</span>
            @else
                <span>Tidak ada antrean</span>
            @endif
        </x-stat-card>
        <x-stat-card label="Siap Ditransfer" :value="rupiah($stats['approved_sum'])" :caption="$stats['approved_count'].' pengajuan sudah disetujui'">
            <span class="text-white/80">{{ $stats['approved_count'] ? 'Segera transfer' : 'Belum ada' }}</span>
            <a href="{{ route('admin.withdrawals.index', ['status' => 'approved']) }}" class="font-bold text-white hover:underline">Lihat</a>
        </x-stat-card>
        <x-stat-card label="Dicairkan Bulan Ini" :value="rupiah($stats['paid_month'])" :caption="now()->translatedFormat('F Y')" />
        <x-stat-card label="Total Komisi Dicairkan" :value="rupiah($stats['paid_total'])" caption="Sejak awal platform berjalan" />
    </div>

    {{-- Tab status + pencarian --}}
    <div class="toolbar">
        <div class="tabs">
            @foreach ($tabs as $key => [$label, $total])
                <x-tab-link :href="route('admin.withdrawals.index', array_filter(['status' => $key, 'q' => $search]))" :active="$status === $key" :count="$total">{{ $label }}</x-tab-link>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="search">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined">search</span>
            <input name="q" value="{{ $search }}" type="text" placeholder="Cari afiliator, no. rekening...">
        </form>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Tabel pengajuan --}}
        <section class="card overflow-hidden 2xl:col-span-8">
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>Afiliator</th>
                            <th>Tujuan Rekening</th>
                            <th>Nominal</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($withdrawals as $withdrawal)
                            @php
                                $isSelected = $selected?->is($withdrawal);
                                $selectUrl = route('admin.withdrawals.index', array_filter(['status' => $status, 'q' => $search, 'page' => $withdrawals->currentPage(), 'withdrawal' => $withdrawal->id]));
                            @endphp
                            <tr @class(['!bg-blue-50/60' => $isSelected])>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $withdrawal->affiliate->name }}</span>
                                    <span class="block text-[13px] text-slate-400">{{ $withdrawal->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    <x-badge :tone="$withdrawal->status->tone()" class="mt-2">{{ $withdrawal->status->label() }}</x-badge>
                                </td>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $withdrawal->bank_name }} &bull; <span class="tabular">{{ $withdrawal->account_number }}</span></span>
                                    <span class="block text-[13px] text-slate-400">a.n. {{ $withdrawal->account_holder }}</span>
                                </td>
                                <td class="tabular whitespace-nowrap font-bold text-slate-900">{{ rupiah($withdrawal->amount) }}</td>
                                <td class="text-right">
                                    <a href="{{ $selectUrl }}" class="btn-soft">Periksa</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-500">Tidak ada pengajuan pencairan{{ $search ? ' untuk pencarian "'.$search.'"' : '' }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 sm:px-7">
                <span class="text-sm text-slate-500">{{ $withdrawals->firstItem() ?? 0 }}-{{ $withdrawals->lastItem() ?? 0 }} dari {{ $withdrawals->total() }} pengajuan</span>
                <div class="flex items-center gap-2">
                    <a href="{{ $withdrawals->previousPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => $withdrawals->onFirstPage()])>Sebelumnya</a>
                    <a href="{{ $withdrawals->nextPageUrl() ?? '#' }}" @class(['btn-outline btn-sm', 'pointer-events-none opacity-40' => ! $withdrawals->hasMorePages()])>Berikutnya</a>
                </div>
            </div>
        </section>

        {{-- Panel inspeksi --}}
        <div class="2xl:col-span-4">
            @if ($selected)
                @php
                    $isOpen = in_array($selected->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved]);
                    $insufficient = $balance['remaining'] < 0;
                @endphp
                <section id="inspeksi" class="card scroll-mt-24"
                         x-init="if (new URLSearchParams(location.search).has('withdrawal') && innerWidth < 1536) $el.scrollIntoView()"
                         x-data="{ rejecting: {{ $errors->has('note') ? 'true' : 'false' }}, copied: false }">
                    <div class="card-header items-center">
                        <div class="min-w-0">
                            <h2 class="card-title truncate">{{ $selected->affiliate->name }}</h2>
                            <p class="card-subtitle">Diajukan {{ $selected->created_at->diffForHumans() }}</p>
                        </div>
                        <x-badge :tone="$selected->status->tone()">{{ $selected->status->label() }}</x-badge>
                    </div>

                    <div class="card-body flex flex-col gap-5">
                        {{-- Rekening tujuan --}}
                        <div class="panel">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Transfer ke</p>
                            <p class="tabular mt-1 text-[1.75rem] font-bold tracking-tight text-slate-900">{{ rupiah($selected->amount) }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <p class="tabular font-semibold text-slate-800">{{ $selected->bank_name }} &bull; {{ $selected->account_number }}</p>
                                    <p class="text-[13px] text-slate-400">a.n. {{ $selected->account_holder }}</p>
                                </div>
                                <button type="button" class="btn-soft"
                                        @click="navigator.clipboard.writeText(@js($selected->account_number)); copied = true; setTimeout(() => copied = false, 1500)"
                                        x-text="copied ? 'Tersalin' : 'Salin'"></button>
                            </div>
                        </div>

                        {{-- Cek saldo --}}
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-800">Cek Saldo Komisi Afiliator</p>
                            <dl @class(['tabular grid gap-2 rounded-xl border p-4 text-sm', 'border-slate-200/80 bg-slate-50/60' => ! $insufficient, 'border-red-100 bg-red-50/60' => $insufficient])>
                                <div class="flex justify-between"><dt class="text-slate-500">Komisi disetujui</dt><dd class="font-semibold text-slate-800">{{ rupiah($balance['earned']) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Diajukan / dicairkan</dt><dd class="text-slate-500">−{{ rupiah($balance['withdrawn']) }}</dd></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2">
                                    <dt class="font-medium text-slate-800">Sisa saldo</dt>
                                    <dd @class(['font-bold', 'text-blue-600' => ! $insufficient, 'text-red-600' => $insufficient])>{{ rupiah($balance['remaining']) }}</dd>
                                </div>
                            </dl>
                            @if ($insufficient && $isOpen)
                                <p class="mt-2 text-[13px] text-red-600">Total pengajuan melebihi komisi afiliator. Sebaiknya tolak pengajuan ini.</p>
                            @endif
                        </div>

                        {{-- Aksi --}}
                        @if ($selected->status === WithdrawalStatus::Pending)
                            <form method="POST" action="{{ route('admin.withdrawals.approve', $selected) }}">
                                @csrf
                                <button @disabled($insufficient) class="btn-primary w-full">Setujui Pengajuan</button>
                            </form>
                        @elseif ($selected->status === WithdrawalStatus::Approved)
                            <form method="POST" action="{{ route('admin.withdrawals.pay', $selected) }}" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                                @csrf
                                <label for="transfer_proof" class="text-sm font-semibold text-emerald-800">Bukti transfer (jpg/png/pdf, maks. 5 MB)</label>
                                <input id="transfer_proof" name="transfer_proof" type="file" accept="image/*,.pdf" required
                                       class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-700">
                                <x-input-error :messages="$errors->get('transfer_proof')" />
                                <input name="transfer_note" type="text" value="{{ old('transfer_note') }}" placeholder="Catatan (opsional), mis. no. referensi" class="input">
                                <x-input-error :messages="$errors->get('transfer_note')" />
                                <button class="btn-success w-full">Tandai Sudah Ditransfer</button>
                            </form>
                        @endif

                        @if ($isOpen)
                            <button type="button" x-show="!rejecting" @click="rejecting = true" class="btn-danger w-full">Tolak Pengajuan</button>
                            <form method="POST" action="{{ route('admin.withdrawals.reject', $selected) }}" x-show="rejecting" x-cloak class="flex flex-col gap-2 rounded-xl border border-red-100 bg-red-50/50 p-4">
                                @csrf
                                <label for="note" class="text-sm font-semibold text-red-700">Alasan penolakan (terlihat oleh afiliator)</label>
                                <textarea id="note" name="note" rows="3" placeholder="Contoh: nama pemilik rekening tidak sesuai" class="input">{{ $errors->has('note') ? old('note') : '' }}</textarea>
                                <x-input-error :messages="$errors->get('note')" />
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="rejecting = false" class="btn-outline btn-sm">Batal</button>
                                    <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700">Tolak Pencairan</button>
                                </div>
                            </form>
                        @endif

                        {{-- Riwayat proses --}}
                        @if ($selected->processed_at)
                            <div @class(['rounded-xl p-4 text-sm', 'bg-emerald-50 text-emerald-700' => $selected->status !== WithdrawalStatus::Rejected, 'bg-red-50 text-red-700' => $selected->status === WithdrawalStatus::Rejected])>
                                {{ $selected->status->label() }} oleh {{ $selected->processor?->name ?? '-' }} &bull; {{ $selected->processed_at->translatedFormat('d M Y, H:i') }} WIB
                                @if ($selected->note)
                                    <p class="mt-1 text-[13px]">{{ $selected->status === WithdrawalStatus::Rejected ? 'Alasan' : 'Catatan' }}: {{ $selected->note }}</p>
                                @endif
                                @if ($selected->transfer_proof)
                                    <a href="{{ route('admin.withdrawals.proof', $selected) }}" target="_blank" class="mt-2 inline-block font-semibold underline">Lihat bukti transfer</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            @else
                <section class="card px-7 py-12 text-center">
                    <p class="font-semibold text-slate-800">Pilih pengajuan</p>
                    <p class="mt-1 text-sm text-slate-500">Klik "Periksa" pada tabel untuk memeriksa saldo dan memproses pencairan.</p>
                </section>
            @endif
        </div>
    </div>
</x-layouts.admin>
