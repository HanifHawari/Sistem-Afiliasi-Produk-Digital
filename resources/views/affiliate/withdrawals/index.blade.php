<x-layouts.affiliate title="Penarikan Komisi">

    @php
        $maskAccount = static function (?string $number): string {
            if (! $number) {
                return '—';
            }
            $len = strlen($number);
            if ($len <= 4) {
                return '•••• ' . $number;
            }
            $start = substr($number, 0, min(4, max(2, (int) floor(($len - 4) / 2) + 2)));
            $end = substr($number, -4);
            return $start . ' •••• ' . $end;
        };
    @endphp

    {{-- ================================================================
         1. PAGE HEADER
         ================================================================ --}}
    <x-page-header
        eyebrow="Keuangan"
        title="Penarikan Komisi"
        description="Ajukan pencairan komisi dan pantau riwayat penarikan Anda."
    />

    {{-- ================================================================
         2. KARTU RINGKASAN
         ================================================================ --}}
    <x-stat-grid cols="3">
        {{-- Kartu 1: Saldo Tersedia --}}
        <x-stat-card
            label="Saldo Tersedia"
            :value="rupiah($balance)"
            caption="Total komisi disetujui yang siap ditarik"
        >
            <span>Minimal penarikan</span>
            <span class="font-bold text-white">Rp 50.000</span>
        </x-stat-card>

        {{-- Kartu 2: Rekening Tujuan --}}
        <x-stat-card
            label="Rekening Tujuan"
            :value="$isBankComplete ? $profile->bank_name : 'Belum Lengkap'"
            :caption="$isBankComplete ? $maskAccount($profile->account_number).' · a.n. '.$profile->account_holder : 'Rekening bank belum diatur'"
        >
            <span class="text-white/80">{{ $isBankComplete ? 'Tersimpan' : 'Wajib diisi' }}</span>
            <a href="{{ route('affiliate.bank-account.edit') }}" class="font-bold text-white hover:underline">
                {{ $isBankComplete ? 'Ubah Rekening' : 'Lengkapi Rekening' }}
            </a>
        </x-stat-card>

        {{-- Kartu 3: Status Rekening --}}
        <x-stat-card
            label="Status Rekening"
            :value="$isBankComplete ? 'Siap Dicairkan' : 'Perlu Dilengkapi'"
            :caption="$isBankComplete ? 'Data rekening valid untuk transfer' : 'Isi form rekening untuk dapat menarik komisi'"
        >
            @if ($isBankComplete)
                <span class="pill-on-blue text-emerald-600">Siap Ditransfer</span>
                <span class="text-white/80">Rekening valid</span>
            @else
                <span class="pill-on-blue text-amber-600">Belum Lengkap</span>
                <span class="text-white/80">Belum bisa tarik</span>
            @endif
        </x-stat-card>
    </x-stat-grid>

    <div class="flex flex-col gap-6">

        {{-- ================================================================
             3. FORM PENARIKAN KOMISI
             ================================================================ --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Ajukan Pencairan Saldo</h2>
                    <p class="card-subtitle">
                        Masukkan nominal komisi yang ingin Anda tarik. Pengajuan akan diverifikasi oleh admin sebelum dana ditransfer.
                    </p>
                </div>
            </div>

            <div class="card-body">
                {{-- Banner Peringatan jika Rekening Belum Lengkap --}}
                @if (! $isBankComplete)
                    <div class="mb-5 flex items-start gap-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4">
                        <span class="material-symbols-outlined shrink-0 text-[22px] text-amber-500">warning</span>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-amber-800">Rekening bank belum lengkap</p>
                            <p class="mt-0.5 text-xs text-amber-700">
                                Anda harus melengkapi data rekening bank terlebih dahulu sebelum dapat mengajukan penarikan komisi.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('affiliate.bank-account.edit') }}" class="btn-primary btn-sm inline-flex items-center gap-1.5 text-xs">
                                    <span class="material-symbols-outlined text-[16px]">edit</span>
                                    Lengkapi Rekening Bank
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif ($balance < 50000)
                    <div class="mb-5 flex items-start gap-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                        <span class="material-symbols-outlined shrink-0 text-[22px] text-slate-400">info</span>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-800">Saldo belum mencapai minimum penarikan</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Saldo tersedia Anda saat ini adalah {{ rupiah($balance) }}. Minimum penarikan adalah Rp 50.000. Promosikan link referral produk digital Anda untuk menghasilkan komisi yang disetujui.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('affiliate.products.index') }}" class="btn-outline btn-sm inline-flex items-center gap-1.5 text-xs">
                                    <span class="material-symbols-outlined text-[16px]">storefront</span>
                                    Buka Katalog Produk
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form Pengajuan Penarikan --}}
                <form
                    method="POST"
                    action="{{ route('affiliate.withdrawals.store') }}"
                    class="max-w-xl"
                    x-data="{
                        amount: '{{ old('amount', '') }}',
                        submitting: false,
                        maxAmount: {{ max(0, $balance) }},
                        setAll() {
                            this.amount = this.maxAmount >= 50000 ? this.maxAmount : '';
                        }
                    }"
                    @submit="submitting = true"
                >
                    @csrf

                    <div>
                        <x-input-label for="amount">
                            Nominal Penarikan
                            <span class="font-normal text-slate-400">(angka bulat)</span>
                        </x-input-label>

                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                                Rp
                            </span>
                            <x-text-input
                                id="amount"
                                name="amount"
                                type="number"
                                min="50000"
                                max="{{ max(0, $balance) }}"
                                step="1"
                                x-model="amount"
                                class="w-full pl-11 pr-32 font-mono @error('amount') ring-red-400 focus:ring-red-500 @enderror"
                                placeholder="0"
                                :disabled="!$isBankComplete || $balance < 50000"
                                required
                            />
                            <div class="absolute right-2 top-1/2 -translate-y-1/2">
                                <button
                                    type="button"
                                    @click="setAll()"
                                    :disabled="!$isBankComplete || $balance < 50000"
                                    class="btn-outline btn-sm py-1 px-2.5 text-xs font-semibold text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Tarik Semua Saldo
                                </button>
                            </div>
                        </div>

                        <p class="mt-1.5 text-xs text-slate-500">
                            Minimal penarikan Rp 50.000.
                        </p>

                        <x-input-error :messages="$errors->get('amount')" class="mt-1.5" />

                        <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-slate-500">
                                Maksimal penarikan:
                                <span class="font-semibold text-slate-800">{{ rupiah($balance) }}</span>
                            </p>
                            @if ($isBankComplete)
                                <p class="text-xs text-slate-400">
                                    Dana ditransfer ke: <span class="font-semibold text-slate-600">{{ $profile->bank_name }} ({{ $maskAccount($profile->account_number) }})</span>
                                </p>
                            @endif
                        </div>

                        <div class="mt-5">
                            <button
                                type="submit"
                                :disabled="submitting || !$isBankComplete || $balance < 50000"
                                class="btn-primary inline-flex items-center justify-center gap-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-[18px]" x-show="!submitting">payments</span>
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" x-show="submitting" x-cloak></span>
                                <span x-text="submitting ? 'Mengajukan...' : 'Ajukan Penarikan'">Ajukan Penarikan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- ================================================================
             4. RIWAYAT PENARIKAN
             ================================================================ --}}
        <section class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Riwayat Penarikan</h2>
                    <p class="card-subtitle">
                        Daftar seluruh pengajuan penarikan komisi yang pernah Anda buat dan status terkininya.
                    </p>
                </div>
            </div>

            @if ($withdrawals->isNotEmpty())
                {{-- A. TAMPILAN DESKTOP (TABLE) --}}
                <div class="hidden lg:block">
                    <div class="relative overflow-x-auto">
                        <table class="data-table ledger">
                            <thead>
                                <tr>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Rekening Tujuan</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($withdrawals as $withdrawal)
                                    <tr>
                                        {{-- Tanggal --}}
                                        <td class="whitespace-nowrap">
                                            <div class="font-semibold text-slate-800">
                                                {{ $withdrawal->created_at?->translatedFormat('d M Y') ?? '—' }}
                                            </div>
                                            <div class="text-[13px] text-slate-400">
                                                {{ $withdrawal->created_at?->translatedFormat('H:i') ?? '' }} WIB
                                            </div>
                                        </td>

                                        {{-- Rekening Tujuan (Snapshot) --}}
                                        <td>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900">{{ $withdrawal->bank_name }}</p>
                                                <p class="text-[13px] text-slate-400">
                                                    <span class="font-mono font-medium text-slate-700">{{ $maskAccount($withdrawal->account_number) }}</span>
                                                    · a.n. <span class="font-medium text-slate-800">{{ $withdrawal->account_holder }}</span>
                                                </p>
                                            </div>
                                        </td>

                                        {{-- Nominal --}}
                                        <td class="tabular whitespace-nowrap font-bold text-slate-900">
                                            {{ rupiah($withdrawal->amount) }}
                                        </td>

                                        {{-- Status --}}
                                        <td class="whitespace-nowrap">
                                            <x-badge :tone="$withdrawal->status->tone()">
                                                {{ $withdrawal->status->label() }}
                                            </x-badge>
                                        </td>

                                        {{-- Catatan --}}
                                        <td class="text-xs text-slate-600">
                                            @if ($withdrawal->note)
                                                <div class="max-w-xs">
                                                    @if ($withdrawal->status === \App\Enums\WithdrawalStatus::Rejected)
                                                        <span class="font-semibold text-red-600">Alasan: </span>
                                                    @endif
                                                    <span class="line-clamp-2" title="{{ $withdrawal->note }}">
                                                        {{ $withdrawal->note }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pager Desktop --}}
                    @if ($withdrawals->hasPages())
                        <x-pager :paginator="$withdrawals" noun="pengajuan penarikan" />
                    @endif
                </div>

                {{-- B. TAMPILAN MOBILE (KARTU) --}}
                <div class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:p-5 lg:hidden">
                    @foreach ($withdrawals as $withdrawal)
                        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $withdrawal->created_at?->translatedFormat('d M Y, H:i') ?? '—' }} WIB
                                    </span>
                                    <p class="mt-0.5 text-lg font-bold text-slate-900">
                                        {{ rupiah($withdrawal->amount) }}
                                    </p>
                                </div>
                                <x-badge :tone="$withdrawal->status->tone()">
                                    {{ $withdrawal->status->label() }}
                                </x-badge>
                            </div>

                            <div class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                <p class="font-semibold text-slate-900">{{ $withdrawal->bank_name }}</p>
                                <p class="mt-0.5 font-mono text-slate-700">{{ $maskAccount($withdrawal->account_number) }}</p>
                                <p class="mt-0.5 text-slate-500">a.n. {{ $withdrawal->account_holder }}</p>
                            </div>

                            @if ($withdrawal->note)
                                <div class="mt-2.5 rounded-lg border border-slate-100 bg-slate-50/70 p-2 text-xs text-slate-600">
                                    <span class="font-semibold text-slate-700">{{ $withdrawal->status === \App\Enums\WithdrawalStatus::Rejected ? 'Alasan Penolakan:' : 'Catatan:' }}</span> {{ $withdrawal->note }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Pager Mobile --}}
                    @if ($withdrawals->hasPages())
                        <div class="rounded-xl border border-slate-200/80 bg-white">
                            <x-pager :paginator="$withdrawals" noun="pengajuan penarikan" />
                        </div>
                    @endif
                </div>

            @else
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center border-t border-slate-100 px-6 py-14 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <span class="material-symbols-outlined text-[32px]">history</span>
                    </div>
                    <h4 class="mt-4 text-base font-bold text-slate-900">Belum Ada Riwayat Penarikan</h4>
                    <p class="mt-1 max-w-sm text-xs text-slate-500">
                        Anda belum pernah membuat pengajuan penarikan komisi. Setelah Anda mengajukan penarikan, riwayatnya akan muncul di sini.
                    </p>
                </div>
            @endif
        </section>

    </div>

</x-layouts.affiliate>
