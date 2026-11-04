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
    <x-page-header eyebrow="Transaksi" title="Pencairan Komisi Afiliator"
        description="Periksa saldo komisi afiliator, setujui pengajuan, lalu tandai lunas setelah dana ditransfer.">
        @if ($stats['pending_count'] > 0)
            <x-slot name="meta">{{ $stats['pending_count'] }} pengajuan butuh tindakan</x-slot>
        @endif
    </x-page-header>

    {{-- Statistik --}}
    <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
        <x-stat-card label="Pengajuan Baru" icon="pending_actions" tone="tertiary" :value="rupiah($stats['pending_sum'])"
            :caption="$stats['pending_count'].' pengajuan menunggu persetujuan'">
            @if ($stats['pending_oldest'])
                <span class="flex items-center gap-1 text-error">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    Tertua {{ $stats['pending_oldest']->diffForHumans() }}
                </span>
            @else
                <span class="flex items-center gap-1 text-secondary">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Tidak ada antrean
                </span>
            @endif
        </x-stat-card>
        <x-stat-card label="Siap Ditransfer" icon="send_money" highlight value-class="text-primary" :value="rupiah($stats['approved_sum'])"
            :caption="$stats['approved_count'].' pengajuan sudah disetujui'">
            @if ($stats['approved_count'] > 0)
                <x-badge tone="info" pulse class="bg-surface-container-lowest">Perlu Ditransfer</x-badge>
                <a href="{{ route('admin.withdrawals.index', ['status' => 'approved']) }}" class="text-primary hover:underline">Lihat →</a>
            @else
                <span class="text-on-surface-variant">Belum ada</span>
            @endif
        </x-stat-card>
        <x-stat-card label="Dicairkan Bulan Ini" icon="payments" tone="secondary" value-class="text-secondary" :value="rupiah($stats['paid_month'])"
            :caption="now()->translatedFormat('F Y')" />
        <x-stat-card label="Total Komisi Dicairkan" icon="account_balance_wallet" :value="rupiah($stats['paid_total'])" caption="Sejak awal platform berjalan" />
    </div>

    {{-- Tab status + pencarian --}}
    <div class="mb-space-lg flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-surface-container-low p-1.5">
        <div class="no-scrollbar flex w-full items-center gap-1 overflow-x-auto sm:w-auto sm:flex-wrap">
            @foreach ($tabs as $key => [$label, $total])
                <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $key, 'q' => $search])) }}"
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
                    <span @class([
                        'rounded-full px-1.5 py-0.5 font-caption text-caption',
                        'bg-tertiary-fixed text-on-tertiary-fixed' => $status === $key,
                        'bg-surface-container-highest text-on-surface' => $status !== $key,
                    ])>{{ $total }}</span>
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="flex w-full items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-sm py-1 sm:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined text-[18px] text-outline">search</span>
            <input name="q" value="{{ $search }}" type="text" placeholder="Cari afiliator, no. rekening..."
                   class="w-full border-0 bg-transparent p-1 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:ring-0 sm:w-60">
        </form>
    </div>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Tabel pengajuan --}}
        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="ledger w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-3 py-3">Afiliator</th>
                            <th class="px-3 py-3">Tujuan Rekening</th>
                            <th class="px-3 py-3 text-right">Nominal</th>
                            <th class="px-3 py-3">Pengajuan &amp; Status</th>
                            <th class="px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse ($withdrawals as $withdrawal)
                            @php
                                $isSelected = $selected?->is($withdrawal);
                                $selectUrl = route('admin.withdrawals.index', array_filter(['status' => $status, 'q' => $search, 'page' => $withdrawals->currentPage(), 'withdrawal' => $withdrawal->id]));
                            @endphp
                            <tr @class([
                                'transition-colors',
                                'bg-primary-fixed/30 hover:bg-primary-fixed/40' => $isSelected,
                                'hover:bg-surface-container-low' => ! $isSelected,
                            ])>
                                <td class="px-3 py-3.5 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-label-sm text-label-sm text-on-primary-fixed-variant">{{ str($withdrawal->affiliate->name)->substr(0, 1)->upper() }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ $selectUrl }}" @class(['font-label-md text-label-md hover:underline', 'font-bold text-primary' => $isSelected, 'text-on-surface' => ! $isSelected])>{{ $withdrawal->affiliate->name }}</a>
                                            <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->affiliate->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold text-primary">{{ $withdrawal->bank_name }}</span>
                                        <div>
                                            <p class="tabular whitespace-nowrap font-label-sm text-label-sm text-on-surface">{{ $withdrawal->account_number }}</p>
                                            <p class="font-caption text-caption text-on-surface-variant">a.n {{ $withdrawal->account_holder }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="tabular whitespace-nowrap px-3 py-3.5 text-right align-top font-label-md text-label-md font-bold text-secondary">{{ rupiah($withdrawal->amount) }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                    <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                                    <x-badge :tone="$withdrawal->status->tone()" :pulse="$withdrawal->status === WithdrawalStatus::Pending" class="mt-1">{{ $withdrawal->status->label() }}</x-badge>
                                </td>
                                <td class="px-3 py-3.5 text-right align-top">
                                    <a href="{{ $selectUrl }}" class="inline-flex rounded-lg bg-surface-container-high p-1.5 text-on-surface-variant transition-colors hover:bg-surface-container-highest" title="Periksa">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-space-2xl text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined mb-1 block text-[36px] text-outline">account_balance_wallet</span>
                                    Tidak ada pengajuan pencairan{{ $search ? ' untuk pencarian "'.$search.'"' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between bg-surface-container-low px-space-md py-3">
                <span class="font-caption text-caption text-on-surface-variant">
                    Menampilkan {{ $withdrawals->firstItem() ?? 0 }}-{{ $withdrawals->lastItem() ?? 0 }} dari {{ $withdrawals->total() }} pengajuan
                </span>
                <div class="flex items-center gap-1">
                    <a href="{{ $withdrawals->previousPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => $withdrawals->onFirstPage()])>
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                    </a>
                    <span class="px-2 font-label-sm text-label-sm text-on-surface">Hal {{ $withdrawals->currentPage() }} dari {{ $withdrawals->lastPage() }}</span>
                    <a href="{{ $withdrawals->nextPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => ! $withdrawals->hasMorePages()])>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Panel inspeksi --}}
        <div class="flex flex-col gap-space-md xl:col-span-4">
            @if ($selected)
                @php
                    $isOpen = in_array($selected->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved]);
                    $insufficient = $balance['remaining'] < 0;
                @endphp
                <div id="inspeksi" class="flex scroll-mt-20 flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-md shadow-md"
                     x-init="if (new URLSearchParams(location.search).has('withdrawal') && innerWidth < 1280) $el.scrollIntoView()"
                     x-data="{ rejecting: {{ $errors->has('note') ? 'true' : 'false' }}, copied: false }">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span @class([
                                'h-2.5 w-2.5 shrink-0 rounded-full',
                                'bg-tertiary' => $selected->status === WithdrawalStatus::Pending,
                                'bg-primary' => $selected->status === WithdrawalStatus::Approved,
                                'bg-secondary' => $selected->status === WithdrawalStatus::Paid,
                                'bg-error' => $selected->status === WithdrawalStatus::Rejected,
                            ])></span>
                            <span class="truncate font-headline-sm text-headline-sm text-on-surface">Pencairan #{{ $selected->id }}</span>
                        </div>
                        <x-badge :tone="$selected->status->tone()" :dot="false">{{ $selected->status->label() }}</x-badge>
                    </div>

                    {{-- Rekening tujuan --}}
                    <div class="flex flex-col gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                        <span class="font-caption text-caption uppercase tracking-wider text-on-surface-variant">Transfer ke</span>
                        <p class="tabular font-currency-display text-currency-display text-secondary">{{ rupiah($selected->amount) }}</p>
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-surface-container-lowest p-space-sm">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold text-primary">{{ $selected->bank_name }}</span>
                                <div>
                                    <p class="tabular font-label-md text-label-md text-on-surface">{{ $selected->account_number }}</p>
                                    <p class="font-caption text-caption text-on-surface-variant">a.n {{ $selected->account_holder }}</p>
                                </div>
                            </div>
                            <button type="button" title="Salin nomor rekening"
                                    @click="navigator.clipboard.writeText(@js($selected->account_number)); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="flex items-center gap-1 rounded-lg px-2 py-1 font-label-sm text-label-sm text-primary hover:bg-primary-fixed">
                                <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'"></span>
                                <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                            </button>
                        </div>
                        <div class="flex items-center justify-between font-caption text-caption text-on-surface-variant">
                            <span>{{ $selected->affiliate->name }} · {{ $selected->affiliate->phone ?? $selected->affiliate->email }}</span>
                            <span>{{ $selected->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    {{-- Cek saldo --}}
                    <div @class([
                        'flex flex-col gap-1.5 rounded-xl p-space-md font-body-sm text-body-sm',
                        'bg-surface-container-low' => ! $insufficient,
                        'bg-error-container/60' => $insufficient,
                    ])>
                        <span class="font-label-sm text-label-sm font-bold text-on-surface">Cek Saldo Komisi Afiliator</span>
                        <div class="flex items-center justify-between text-on-surface">
                            <span>Total komisi disetujui</span>
                            <span class="tabular font-semibold">{{ rupiah($balance['earned']) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-on-surface-variant">
                            <span>Total diajukan / dicairkan</span>
                            <span class="tabular">−{{ rupiah($balance['withdrawn']) }}</span>
                        </div>
                        <div @class(['flex items-center justify-between border-t border-outline-variant/50 pt-1.5 font-medium', 'text-primary' => ! $insufficient, 'text-error' => $insufficient])>
                            <span>Sisa saldo</span>
                            <span class="tabular font-bold">{{ rupiah($balance['remaining']) }}</span>
                        </div>
                        @if ($insufficient && $isOpen)
                            <p class="mt-1 flex items-start gap-1 font-caption text-caption text-on-error-container">
                                <span class="material-symbols-outlined text-[14px]">warning</span>
                                Total pengajuan melebihi komisi afiliator. Sebaiknya tolak pengajuan ini.
                            </p>
                        @endif
                    </div>

                    {{-- Aksi --}}
                    @if ($selected->status === WithdrawalStatus::Pending)
                        <form method="POST" action="{{ route('admin.withdrawals.approve', $selected) }}">
                            @csrf
                            <button @disabled($insufficient) class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-container px-space-md py-3 font-label-md text-label-md text-on-primary shadow-md transition-all hover:bg-primary active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50">
                                <span class="material-symbols-outlined text-[20px]">task_alt</span>
                                Setujui Pengajuan
                            </button>
                        </form>
                    @elseif ($selected->status === WithdrawalStatus::Approved)
                        <form method="POST" action="{{ route('admin.withdrawals.pay', $selected) }}" enctype="multipart/form-data" class="flex flex-col gap-2 rounded-xl bg-secondary-fixed/30 p-space-sm">
                            @csrf
                            <label for="transfer_proof" class="font-label-sm text-label-sm text-on-secondary-fixed-variant">Bukti transfer (jpg/png/pdf, maks. 5 MB)</label>
                            <input id="transfer_proof" name="transfer_proof" type="file" accept="image/*,.pdf" required
                                   class="w-full font-body-sm text-body-sm text-on-surface-variant file:mr-2 file:rounded-lg file:border-0 file:bg-secondary-fixed file:px-3 file:py-1.5 file:font-label-sm file:text-on-secondary-fixed">
                            <x-input-error :messages="$errors->get('transfer_proof')" />
                            <input name="transfer_note" type="text" value="{{ old('transfer_note') }}" placeholder="Catatan (opsional), mis. no. referensi transfer"
                                   class="w-full rounded-lg border-0 bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-secondary">
                            <x-input-error :messages="$errors->get('transfer_note')" />
                            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-space-md py-3 font-label-md text-label-md text-on-secondary shadow-md transition-all hover:bg-secondary/90 active:scale-[0.99]">
                                <span class="material-symbols-outlined text-[20px]">done_all</span>
                                Tandai Sudah Ditransfer
                            </button>
                        </form>
                    @endif

                    @if ($isOpen)
                        <button type="button" x-show="!rejecting" @click="rejecting = true"
                                class="flex items-center justify-center gap-1 rounded-lg bg-error-container px-space-sm py-2 font-label-sm text-label-sm text-on-error-container transition-colors hover:bg-error/20">
                            <span class="material-symbols-outlined text-[16px]">cancel</span> Tolak Pengajuan
                        </button>
                        <form method="POST" action="{{ route('admin.withdrawals.reject', $selected) }}" x-show="rejecting" x-cloak class="flex flex-col gap-2 rounded-xl bg-error-container/40 p-space-sm">
                            @csrf
                            <label for="note" class="font-label-sm text-label-sm text-on-error-container">Alasan penolakan (terlihat oleh afiliator)</label>
                            <textarea id="note" name="note" rows="3" placeholder="Contoh: nama pemilik rekening tidak sesuai"
                                      class="w-full rounded-lg border-0 bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-error">{{ $errors->has('note') ? old('note') : '' }}</textarea>
                            <x-input-error :messages="$errors->get('note')" />
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="rejecting = false" class="rounded-lg bg-surface-container-high px-space-sm py-2 font-label-sm text-label-sm text-on-surface hover:bg-surface-container-highest">Batal</button>
                                <button class="rounded-lg bg-error px-space-sm py-2 font-label-sm text-label-sm text-on-error hover:bg-error/90">Tolak Pencairan</button>
                            </div>
                        </form>
                    @endif

                    {{-- Riwayat proses --}}
                    @if ($selected->processed_at)
                        <div @class([
                            'flex items-start gap-2 rounded-xl p-space-sm font-body-sm text-body-sm',
                            'bg-secondary-fixed/40 text-on-secondary-fixed-variant' => $selected->status !== WithdrawalStatus::Rejected,
                            'bg-error-container/50 text-on-error-container' => $selected->status === WithdrawalStatus::Rejected,
                        ])>
                            <span class="material-symbols-outlined text-[18px]">history</span>
                            <div class="min-w-0">
                                {{ $selected->status->label() }} oleh {{ $selected->processor?->name ?? '-' }}
                                · {{ $selected->processed_at->translatedFormat('d M Y, H:i') }} WIB
                                @if ($selected->note)
                                    <p class="mt-1 font-caption text-caption">{{ $selected->status === WithdrawalStatus::Rejected ? 'Alasan' : 'Catatan' }}: {{ $selected->note }}</p>
                                @endif
                                @if ($selected->transfer_proof)
                                    <a href="{{ route('admin.withdrawals.proof', $selected) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 font-label-sm text-label-sm text-primary hover:underline">
                                        <span class="material-symbols-outlined text-[16px]">receipt_long</span> Lihat bukti transfer
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center gap-1 rounded-xl bg-surface-container-lowest p-space-xl text-center text-on-surface-variant shadow-sm">
                    <span class="material-symbols-outlined text-[36px] text-outline">fact_check</span>
                    <span class="font-label-md text-label-md text-on-surface">Pilih pengajuan</span>
                    <span class="font-caption text-caption">Klik ikon mata pada tabel untuk memeriksa saldo dan memproses pencairan.</span>
                </div>
            @endif

            <div class="flex items-center gap-space-md rounded-xl bg-surface-container-low p-space-md">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-highest text-primary">
                    <span class="material-symbols-outlined text-[24px]">info</span>
                </div>
                <div class="flex flex-col">
                    <span class="font-label-md text-label-md text-on-surface">Alur pencairan</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">Diajukan → Disetujui → transfer manual ke rekening → unggah bukti → Sudah Ditransfer. Pengajuan yang ditolak mengembalikan saldo afiliator.</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
