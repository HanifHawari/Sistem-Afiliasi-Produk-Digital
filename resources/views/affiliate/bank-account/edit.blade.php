<x-layouts.affiliate title="Rekening Bank">

    {{-- ================================================================
         PAGE HEADER
         ================================================================ --}}
    <x-page-header
        title="Rekening Bank"
        description="Rekening tujuan pencairan komisi Anda."
    />

    <div class="mx-auto max-w-2xl" data-enter="up">

        {{-- ================================================================
             KARTU STATUS REKENING TERSIMPAN
             Ditampilkan hanya jika data sudah pernah disimpan.
             ================================================================ --}}
        @if ($profile && $profile->bank_name)
            <div class="card mb-6 flex items-start gap-4 p-5 sm:p-6">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-[24px]">account_balance</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Rekening Tersimpan</p>
                    <p class="mt-1 text-base font-bold text-slate-900">{{ $profile->bank_name }}</p>
                    <p class="mt-0.5 text-sm text-slate-600">
                        <span class="tabular font-semibold text-blue-600">{{ $profile->account_number }}</span>
                        &bull; a.n. <span class="font-semibold text-slate-800">{{ $profile->account_holder }}</span>
                    </p>
                </div>
                <x-badge tone="success">Aktif</x-badge>
            </div>
        @else
            <div class="mb-6 flex items-start gap-3.5 rounded-2xl border border-dashed border-amber-300 bg-amber-50/70 p-5">
                <span class="material-symbols-outlined shrink-0 text-[22px] text-amber-600">info</span>
                <div>
                    <p class="text-sm font-semibold text-amber-900">Rekening belum ditambahkan</p>
                    <p class="mt-0.5 text-sm text-amber-700">
                        Isi form di bawah agar komisi Anda dapat dicairkan ke rekening bank.
                    </p>
                </div>
            </div>
        @endif

        {{-- ================================================================
             FORM REKENING BANK
             ================================================================ --}}
        <section class="card">

            {{-- Header kartu --}}
            <div class="card-header">
                <div>
                    <h2 class="card-title flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-blue-600">edit</span>
                        {{ $profile && $profile->bank_name ? 'Perbarui Rekening' : 'Tambah Rekening' }}
                    </h2>
                    <p class="card-subtitle">
                        Data ini digunakan admin untuk memproses pencairan komisi Anda.
                    </p>
                </div>
            </div>

            {{-- Body form --}}
            <form method="POST" action="{{ route('affiliate.bank-account.update') }}" class="card-body">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-5">

                    {{-- Nama Bank --}}
                    <div>
                        <x-input-label for="bank_name">
                            Nama Bank
                            <span class="font-normal text-slate-400">(mis. BCA, BRI, Mandiri, BNI)</span>
                        </x-input-label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <span class="material-symbols-outlined text-[18px]">account_balance</span>
                            </span>
                            <x-text-input
                                id="bank_name"
                                name="bank_name"
                                type="text"
                                class="w-full pl-10 {{ $errors->has('bank_name') ? 'ring-red-400 focus:ring-red-500' : '' }}"
                                :value="old('bank_name', $profile?->bank_name)"
                                placeholder="Contoh: BCA"
                                autocomplete="off"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('bank_name')" class="mt-1.5" />
                    </div>

                    {{-- Nomor Rekening --}}
                    <div>
                        <x-input-label for="account_number">
                            Nomor Rekening
                            <span class="font-normal text-slate-400">(angka, maks. 50 digit)</span>
                        </x-input-label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <span class="material-symbols-outlined text-[18px]">pin</span>
                            </span>
                            <x-text-input
                                id="account_number"
                                name="account_number"
                                type="text"
                                inputmode="numeric"
                                class="w-full pl-10 tabular font-mono {{ $errors->has('account_number') ? 'ring-red-400 focus:ring-red-500' : '' }}"
                                :value="old('account_number', $profile?->account_number)"
                                placeholder="Contoh: 1234567890"
                                autocomplete="off"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('account_number')" class="mt-1.5" />
                        <p class="mt-1 text-xs text-slate-400">
                            Pastikan nomor rekening benar. Angka nol di awal akan tetap tersimpan.
                        </p>
                    </div>

                    {{-- Nama Pemilik Rekening --}}
                    <div>
                        <x-input-label for="account_holder">
                            Nama Pemilik Rekening
                            <span class="font-normal text-slate-400">(sesuai buku tabungan)</span>
                        </x-input-label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <span class="material-symbols-outlined text-[18px]">person</span>
                            </span>
                            <x-text-input
                                id="account_holder"
                                name="account_holder"
                                type="text"
                                class="w-full pl-10 {{ $errors->has('account_holder') ? 'ring-red-400 focus:ring-red-500' : '' }}"
                                :value="old('account_holder', $profile?->account_holder)"
                                placeholder="Contoh: Budi Santoso"
                                autocomplete="name"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('account_holder')" class="mt-1.5" />
                    </div>

                </div>

                {{-- Tombol Simpan --}}
                <div class="mt-7 flex items-center justify-end gap-3">
                    <a href="{{ route('affiliate.dashboard') }}"
                       class="btn-outline">
                        Batal
                    </a>
                    <button type="submit"
                            class="btn-primary">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Simpan Rekening
                    </button>
                </div>
            </form>
        </section>

        {{-- ================================================================
             CATATAN KEAMANAN
             ================================================================ --}}
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 p-4 sm:p-5">
            <span class="material-symbols-outlined shrink-0 text-[20px] text-blue-600">lock</span>
            <p class="text-sm leading-relaxed text-blue-700">
                Data rekening bank Anda tersimpan dengan aman dan hanya digunakan untuk proses
                pencairan komisi yang sudah disetujui admin. Pastikan nama pemilik rekening sesuai
                dengan identitas Anda.
            </p>
        </div>

    </div>

</x-layouts.affiliate>
