@php
    $oldForm = old('_form') === 'payment-account' ? collect(old())->except(['_token', '_method', 'qris_image'])->all() : null;

    $toJs = fn ($account) => [
        'id' => $account->id,
        'type' => $account->type->value,
        'bank_name' => $account->bank_name ?? '',
        'account_number' => $account->account_number ?? '',
        'account_holder' => $account->account_holder,
        'is_active' => $account->is_active,
        'qris_url' => $account->qrisImageUrl(),
        'used' => $account->orders_count > 0,
    ];
@endphp

<x-layouts.admin title="QRIS & Rekening">
    <div x-data="paymentAccountPage({
            storeUrl: @js(route('admin.payment-accounts.store')),
            updateUrl: @js(route('admin.payment-accounts.update', '__ID__')),
            old: @js($oldForm),
         })">

        <x-page-header title="QRIS & Rekening"
            description="Atur tujuan pembayaran customer. Metode yang aktif langsung tampil di halaman checkout.">
            <x-slot name="actions">
                <button type="button" @click="create('bank')" class="btn-outline">Tambah Rekening</button>
                <button type="button" @click="create('qris')" class="btn-primary">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Tambah QRIS
                </button>
            </x-slot>
        </x-page-header>

        @if ($active->isEmpty())
            <div class="mb-8 rounded-2xl border border-red-100 bg-red-50 px-6 py-4 text-red-700">
                <p class="font-semibold">Belum ada metode pembayaran yang aktif</p>
                <p class="text-sm">Customer tidak bisa checkout sampai minimal satu QRIS atau rekening diaktifkan.</p>
            </div>
        @endif

        {{-- Statistik --}}
        <x-stat-grid cols="3">
            <x-stat-card label="QRIS Aktif" :value="$stats['active_qris'].' QRIS'" :caption="$qrisAccounts->count().' QRIS terdaftar'" />
            <x-stat-card label="Rekening Aktif" :value="$stats['active_bank'].' rekening'" :caption="$bankAccounts->count().' rekening terdaftar'" />
            <x-stat-card label="Dana Masuk Bulan Ini" :value="rupiah($stats['paid_month'])" caption="Dari pembayaran yang sudah diverifikasi" />
        </x-stat-grid>

        <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
            <div class="flex flex-col gap-6 2xl:col-span-8">
                {{-- QRIS --}}
                <section class="card">
                    <div class="card-header items-center">
                        <div>
                            <h2 class="card-title">QRIS</h2>
                            <p class="card-subtitle">Customer scan kode QR ini dari e-wallet atau m-banking</p>
                        </div>
                        <button type="button" @click="create('qris')" class="card-link">Tambah</button>
                    </div>

                    <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @forelse ($qrisAccounts as $account)
                            <div @class(['flex gap-4 rounded-xl border p-4', 'border-slate-200/80' => $account->is_active, 'border-dashed border-slate-200 opacity-60' => ! $account->is_active])>
                                <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-100 bg-white">
                                    @if ($account->qrisImageUrl())
                                        <img src="{{ $account->qrisImageUrl() }}" alt="QRIS {{ $account->account_holder }}" class="h-full w-full object-contain">
                                    @else
                                        <span class="text-xs text-slate-400">Tanpa gambar</span>
                                    @endif
                                </div>
                                <div class="flex min-w-0 flex-1 flex-col justify-between gap-3">
                                    <div>
                                        <p class="truncate font-semibold text-slate-800">{{ $account->account_holder }}</p>
                                        <x-badge :tone="$account->is_active ? 'success' : 'neutral'" class="mt-1.5">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                        <p class="tabular mt-1.5 text-[13px] text-slate-400">{{ $account->paid_count }} transaksi &bull; {{ rupiah($account->paid_sum) }}</p>
                                    </div>
                                    @include('admin.payment-accounts.partials.actions', ['account' => $account])
                                </div>
                            </div>
                        @empty
                            <button type="button" @click="create('qris')" class="rounded-xl border border-dashed border-slate-300 p-8 text-sm font-semibold text-slate-500 hover:bg-slate-50 sm:col-span-2">
                                Belum ada QRIS. Klik untuk menambahkan.
                            </button>
                        @endforelse
                    </div>
                </section>

                {{-- Rekening bank --}}
                <section class="card overflow-hidden">
                    <div class="card-header items-center">
                        <div>
                            <h2 class="card-title">Rekening Bank</h2>
                            <p class="card-subtitle">Customer transfer manual ke rekening ini lalu mengunggah bukti</p>
                        </div>
                        <button type="button" @click="create('bank')" class="card-link">Tambah</button>
                    </div>
                    <div class="relative overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Bank &amp; Rekening</th>
                                    <th>Dana Masuk</th>
                                    <th>Status</th>
                                    <th><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bankAccounts as $account)
                                    <tr @class(['opacity-60' => ! $account->is_active])>
                                        <td>
                                            <span class="tabular block font-semibold text-slate-800">{{ $account->bank_name }} &bull; {{ $account->account_number }}</span>
                                            <span class="block text-[13px] text-slate-400">a.n. {{ $account->account_holder }}</span>
                                        </td>
                                        <td class="tabular whitespace-nowrap">
                                            <span class="block font-semibold text-slate-800">{{ rupiah($account->paid_sum) }}</span>
                                            <span class="block text-[13px] text-slate-400">{{ $account->paid_count }} transaksi</span>
                                        </td>
                                        <td><x-badge :tone="$account->is_active ? 'success' : 'neutral'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                        <td>@include('admin.payment-accounts.partials.actions', ['account' => $account])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-slate-500">
                                            Belum ada rekening bank.
                                            <button type="button" @click="create('bank')" class="card-link">Tambah sekarang</button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- Pratinjau checkout --}}
            <div class="flex flex-col gap-6 2xl:col-span-4">
                <section class="card">
                    <div class="card-header items-center">
                        <h2 class="card-title">Pratinjau Checkout</h2>
                        <span class="text-sm text-slate-400">Customer</span>
                    </div>
                    <div class="card-body flex flex-col gap-3">
                        <p class="text-sm text-slate-500">Pilihan pembayaran yang dilihat customer saat checkout:</p>
                        @forelse ($active as $account)
                            <div class="rounded-xl border border-slate-200/80 px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $account->isQris() ? 'QRIS' : 'Transfer '.$account->bank_name }}</p>
                                <p class="truncate text-[13px] text-slate-400">
                                    {{ $account->isQris() ? $account->account_holder : $account->account_number.' a.n. '.$account->account_holder }}
                                </p>
                            </div>
                        @empty
                            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Tidak ada pilihan pembayaran. Checkout tidak bisa dilakukan.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-blue-100 bg-blue-50/40 px-6 py-5">
                    <p class="font-semibold text-slate-800">Tips</p>
                    <p class="mt-1 text-sm text-slate-600">Metode yang sudah dipakai transaksi tidak bisa dihapus. Cukup <b>nonaktifkan</b> supaya riwayat pembayaran tetap utuh.</p>
                </section>
            </div>
        </div>

        {{-- Modal tambah / edit --}}
        <x-modal name="payment-account-form" :show="$oldForm !== null" max-width="md" focusable>
            <form method="POST" :action="action" enctype="multipart/form-data" class="flex flex-col gap-5 p-6 sm:p-7">
                @csrf
                <input type="hidden" name="_form" value="payment-account">
                <input type="hidden" name="_account_id" :value="form.id">
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-900"
                        x-text="(mode === 'edit' ? 'Edit ' : 'Tambah ') + (form.type === 'qris' ? 'QRIS' : 'Rekening Bank')"></h3>
                    <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="$dispatch('close-modal', 'payment-account-form')">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div>
                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="qris" x-model="form.type" class="peer sr-only">
                            <span class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-500 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">QRIS</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="bank" x-model="form.type" class="peer sr-only">
                            <span class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-500 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">Rekening Bank</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>

                <template x-if="form.type === 'bank'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="bank_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama bank</label>
                            <input id="bank_name" name="bank_name" list="bank-options" x-model="form.bank_name" placeholder="BCA" class="input uppercase">
                            <datalist id="bank-options">
                                @foreach (['BCA', 'BRI', 'BNI', 'MANDIRI', 'BSI', 'CIMB NIAGA', 'PERMATA', 'BTN', 'SEABANK', 'JAGO'] as $bank)
                                    <option value="{{ $bank }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label for="account_number" class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor rekening</label>
                            <input id="account_number" name="account_number" inputmode="numeric" x-model="form.account_number" placeholder="8091129900" class="input tabular">
                        </div>
                    </div>
                </template>
                <x-input-error :messages="array_merge($errors->get('bank_name'), $errors->get('account_number'))" />

                <div>
                    <label for="account_holder" class="mb-1.5 block text-sm font-semibold text-slate-700" x-text="form.type === 'qris' ? 'Nama merchant (tertera di QRIS)' : 'Atas nama'"></label>
                    <input id="account_holder" name="account_holder" x-model="form.account_holder" required placeholder="PT Afiliasi Produk Digital" class="input">
                    <x-input-error :messages="$errors->get('account_holder')" class="mt-1" />
                </div>

                <template x-if="form.type === 'qris'">
                    <div>
                        <label for="qris_image" class="mb-1.5 block text-sm font-semibold text-slate-700">Gambar QRIS (maks. 2 MB)</label>
                        <div class="flex items-center gap-3">
                            <template x-if="form.qris_url">
                                <img :src="form.qris_url" alt="QRIS saat ini" class="h-16 w-16 rounded-lg border border-slate-100 bg-white object-contain">
                            </template>
                            <input id="qris_image" name="qris_image" type="file" accept="image/*"
                                   class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-600">
                        </div>
                        <p class="mt-1 text-[13px] text-slate-400" x-show="form.qris_url">Kosongkan jika tidak diganti.</p>
                    </div>
                </template>
                <x-input-error :messages="$errors->get('qris_image')" />

                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-700">Aktif (tampil di checkout)</span>
                </label>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
                    <button type="button" @click="$dispatch('close-modal', 'payment-account-form')" class="btn-outline btn-sm py-2.5">Batal</button>
                    <button class="btn-primary btn-sm py-2.5" x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Simpan'"></button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function paymentAccountPage(config) {
            const blank = { id: '', type: 'qris', bank_name: '', account_number: '', account_holder: '', is_active: true, qris_url: null };
            const old = config.old;

            return {
                mode: old && old._account_id ? 'edit' : 'create',
                form: old ? { ...blank, ...old, id: old._account_id, is_active: old.is_active === '1' } : { ...blank },

                get action() {
                    return this.mode === 'edit' ? config.updateUrl.replace('__ID__', this.form.id) : config.storeUrl;
                },
                create(type) {
                    this.mode = 'create';
                    this.form = { ...blank, type };
                    this.$dispatch('open-modal', 'payment-account-form');
                },
                edit(account) {
                    this.mode = 'edit';
                    this.form = { ...blank, ...account };
                    this.$dispatch('open-modal', 'payment-account-form');
                },
            };
        }
    </script>
</x-layouts.admin>
