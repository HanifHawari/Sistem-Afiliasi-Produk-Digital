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

        <x-page-header eyebrow="Kelola" title="QRIS & Rekening"
            description="Atur tujuan pembayaran customer. Metode yang aktif langsung tampil di halaman checkout.">
            <x-slot name="actions">
                <button type="button" @click="create('bank')" class="flex items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-md py-2.5 text-on-surface shadow-sm transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant">account_balance</span>
                    <span class="font-label-md text-label-md">Tambah Rekening</span>
                </button>
                <button type="button" @click="create('qris')" class="flex items-center gap-space-xs rounded-lg bg-primary-container px-space-md py-2.5 text-on-primary shadow-sm transition-all hover:bg-primary">
                    <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                    <span class="font-label-md text-label-md">Tambah QRIS</span>
                </button>
            </x-slot>
        </x-page-header>

        @if ($active->isEmpty())
            <div class="mb-space-lg flex items-start gap-space-sm rounded-xl bg-error-container p-space-md text-on-error-container">
                <span class="material-symbols-outlined">warning</span>
                <div>
                    <p class="font-label-md text-label-md">Belum ada metode pembayaran yang aktif</p>
                    <p class="font-body-sm text-body-sm">Customer tidak bisa checkout sampai minimal satu QRIS atau rekening diaktifkan.</p>
                </div>
            </div>
        @endif

        {{-- Statistik --}}
        <div class="mb-space-lg grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-space-md [&>*:last-child]:col-span-2 sm:[&>*:last-child]:col-span-1">
            <x-stat-card label="QRIS Aktif" icon="qr_code_2" :value="$stats['active_qris'].' QRIS'" :caption="$qrisAccounts->count().' QRIS terdaftar'" />
            <x-stat-card label="Rekening Aktif" icon="account_balance" tone="tertiary" :value="$stats['active_bank'].' rekening'" :caption="$bankAccounts->count().' rekening terdaftar'" />
            <x-stat-card label="Dana Masuk Bulan Ini" icon="savings" tone="secondary" value-class="text-secondary" :value="rupiah($stats['paid_month'])" caption="Dari pembayaran yang sudah diverifikasi" />
        </div>

        <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
            <div class="flex flex-col gap-space-lg xl:col-span-8">
                {{-- QRIS --}}
                <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                    <div class="mb-space-md flex items-center justify-between">
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">QRIS</h2>
                            <p class="font-caption text-caption text-on-surface-variant">Customer scan kode QR ini untuk membayar dari e-wallet atau m-banking apa pun</p>
                        </div>
                        <button type="button" @click="create('qris')" class="rounded-lg p-1.5 text-primary hover:bg-primary-fixed" title="Tambah QRIS">
                            <span class="material-symbols-outlined">add</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2">
                        @forelse ($qrisAccounts as $account)
                            <div @class(['flex gap-space-md rounded-xl border p-space-sm', 'border-surface-container bg-surface-container-low' => $account->is_active, 'border-dashed border-outline-variant bg-surface opacity-70' => ! $account->is_active])>
                                <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white">
                                    @if ($account->qrisImageUrl())
                                        <img src="{{ $account->qrisImageUrl() }}" alt="QRIS {{ $account->account_holder }}" class="h-full w-full object-contain">
                                    @else
                                        <span class="material-symbols-outlined text-[40px] text-outline">qr_code_2</span>
                                    @endif
                                </div>
                                <div class="flex min-w-0 flex-1 flex-col justify-between">
                                    <div>
                                        <p class="truncate font-label-md text-label-md text-on-surface">{{ $account->account_holder }}</p>
                                        <x-badge :tone="$account->is_active ? 'success' : 'neutral'" class="mt-1">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                        <p class="tabular mt-1 font-caption text-caption text-on-surface-variant">{{ $account->paid_count }} transaksi · {{ rupiah($account->paid_sum) }}</p>
                                    </div>
                                    @include('admin.payment-accounts.partials.actions', ['account' => $account])
                                </div>
                            </div>
                        @empty
                            <button type="button" @click="create('qris')" class="flex flex-col items-center gap-1 rounded-xl border border-dashed border-outline-variant p-space-lg text-on-surface-variant hover:bg-surface-container-low sm:col-span-2">
                                <span class="material-symbols-outlined text-[32px] text-outline">qr_code_2</span>
                                <span class="font-label-md text-label-md">Belum ada QRIS. Klik untuk menambahkan.</span>
                            </button>
                        @endforelse
                    </div>
                </section>

                {{-- Rekening bank --}}
                <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                    <div class="flex items-center justify-between p-space-md">
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">Rekening Bank</h2>
                            <p class="font-caption text-caption text-on-surface-variant">Customer transfer manual ke rekening ini lalu mengunggah bukti</p>
                        </div>
                        <button type="button" @click="create('bank')" class="rounded-lg p-1.5 text-primary hover:bg-primary-fixed" title="Tambah rekening">
                            <span class="material-symbols-outlined">add</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                                    <th class="px-space-md py-3">Bank &amp; Rekening</th>
                                    <th class="px-space-md py-3 text-right">Dana Masuk</th>
                                    <th class="px-space-md py-3 text-center">Status</th>
                                    <th class="px-space-md py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                @forelse ($bankAccounts as $account)
                                    <tr @class(['hover:bg-surface-container-low/60', 'opacity-60' => ! $account->is_active])>
                                        <td class="px-space-md py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-10 min-w-[3.5rem] items-center justify-center rounded-lg bg-primary-fixed px-2 font-label-sm text-label-sm font-bold text-primary">{{ $account->bank_name }}</span>
                                                <div>
                                                    <p class="tabular font-label-md text-label-md text-on-surface">{{ $account->account_number }}</p>
                                                    <p class="font-caption text-caption text-on-surface-variant">a.n. {{ $account->account_holder }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tabular whitespace-nowrap px-space-md py-3 text-right">
                                            <p class="font-label-md text-label-md text-on-surface">{{ rupiah($account->paid_sum) }}</p>
                                            <p class="font-caption text-caption text-on-surface-variant">{{ $account->paid_count }} transaksi</p>
                                        </td>
                                        <td class="px-space-md py-3 text-center">
                                            <x-badge :tone="$account->is_active ? 'success' : 'neutral'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                        </td>
                                        <td class="px-space-md py-3">
                                            @include('admin.payment-accounts.partials.actions', ['account' => $account])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-space-md py-space-xl text-center text-on-surface-variant">
                                            Belum ada rekening bank.
                                            <button type="button" @click="create('bank')" class="font-label-md text-label-md text-primary hover:underline">Tambah sekarang</button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- Pratinjau checkout --}}
            <div class="flex flex-col gap-space-md xl:col-span-4">
                <div class="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-md shadow-md">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px] text-primary">visibility</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Pratinjau Checkout</h3>
                        </div>
                        <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold uppercase text-on-primary-fixed">Customer</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Pilihan pembayaran yang dilihat customer saat checkout:</p>

                    <div class="flex flex-col gap-space-sm">
                        @forelse ($active as $account)
                            <div class="flex items-center gap-space-sm rounded-lg border border-slate-200 bg-white p-space-sm">
                                <span class="material-symbols-outlined text-primary">{{ $account->isQris() ? 'qr_code_2' : 'account_balance' }}</span>
                                <div class="min-w-0">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $account->isQris() ? 'QRIS' : 'Transfer '.$account->bank_name }}</p>
                                    <p class="truncate font-caption text-caption text-on-surface-variant">
                                        {{ $account->isQris() ? $account->account_holder : $account->account_number.' a.n. '.$account->account_holder }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg bg-error-container/60 p-space-sm font-body-sm text-body-sm text-on-error-container">Tidak ada pilihan pembayaran. Checkout tidak bisa dilakukan.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-start gap-space-md rounded-xl bg-surface-container-low p-space-md">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-highest text-primary">
                        <span class="material-symbols-outlined text-[24px]">info</span>
                    </div>
                    <div class="flex flex-col gap-1 font-body-sm text-body-sm text-on-surface-variant">
                        <span class="font-label-md text-label-md text-on-surface">Tips</span>
                        <span>Metode yang sudah dipakai transaksi tidak bisa dihapus, cukup <b>nonaktifkan</b> supaya riwayat pembayaran tetap utuh.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal tambah / edit --}}
        <x-modal name="payment-account-form" :show="$oldForm !== null" max-width="md" focusable>
            <form method="POST" :action="action" enctype="multipart/form-data" class="flex flex-col gap-space-md p-space-lg">
                @csrf
                <input type="hidden" name="_form" value="payment-account">
                <input type="hidden" name="_account_id" :value="form.id">
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface"
                        x-text="(mode === 'edit' ? 'Edit ' : 'Tambah ') + (form.type === 'qris' ? 'QRIS' : 'Rekening Bank')"></h3>
                    <button type="button" class="text-on-surface-variant hover:text-on-surface" @click="$dispatch('close-modal', 'payment-account-form')">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div>
                    <span class="font-label-sm text-label-sm text-on-surface">Jenis</span>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="qris" x-model="form.type" class="peer sr-only">
                            <span class="flex items-center justify-center gap-1 rounded-lg border border-outline-variant py-2 font-label-sm text-label-sm text-on-surface-variant peer-checked:border-2 peer-checked:border-primary-container peer-checked:text-primary">
                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span> QRIS
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="bank" x-model="form.type" class="peer sr-only">
                            <span class="flex items-center justify-center gap-1 rounded-lg border border-outline-variant py-2 font-label-sm text-label-sm text-on-surface-variant peer-checked:border-2 peer-checked:border-primary-container peer-checked:text-primary">
                                <span class="material-symbols-outlined text-[18px]">account_balance</span> Rekening Bank
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>

                <template x-if="form.type === 'bank'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="bank_name" class="font-label-sm text-label-sm text-on-surface">Nama Bank</label>
                            <input id="bank_name" name="bank_name" list="bank-options" x-model="form.bank_name" placeholder="BCA"
                                   class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 uppercase focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                            <datalist id="bank-options">
                                @foreach (['BCA', 'BRI', 'BNI', 'MANDIRI', 'BSI', 'CIMB NIAGA', 'PERMATA', 'BTN', 'SEABANK', 'JAGO'] as $bank)
                                    <option value="{{ $bank }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label for="account_number" class="font-label-sm text-label-sm text-on-surface">Nomor Rekening</label>
                            <input id="account_number" name="account_number" inputmode="numeric" x-model="form.account_number" placeholder="8091129900"
                                   class="tabular mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                        </div>
                    </div>
                </template>
                <x-input-error :messages="array_merge($errors->get('bank_name'), $errors->get('account_number'))" />

                <div>
                    <label for="account_holder" class="font-label-sm text-label-sm text-on-surface" x-text="form.type === 'qris' ? 'Nama Merchant (tertera di QRIS)' : 'Atas Nama'"></label>
                    <input id="account_holder" name="account_holder" x-model="form.account_holder" required placeholder="PT Afiliasi Produk Digital"
                           class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                    <x-input-error :messages="$errors->get('account_holder')" class="mt-1" />
                </div>

                <template x-if="form.type === 'qris'">
                    <div>
                        <label for="qris_image" class="font-label-sm text-label-sm text-on-surface">Gambar QRIS (maks. 2 MB)</label>
                        <div class="mt-1 flex items-center gap-3">
                            <template x-if="form.qris_url">
                                <img :src="form.qris_url" alt="QRIS saat ini" class="h-16 w-16 rounded-lg bg-white object-contain">
                            </template>
                            <input id="qris_image" name="qris_image" type="file" accept="image/*"
                                   class="w-full font-body-sm text-body-sm text-on-surface-variant file:mr-2 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-1.5 file:font-label-sm file:text-on-primary-fixed-variant">
                        </div>
                        <p class="mt-1 font-caption text-caption text-on-surface-variant" x-show="form.qris_url">Kosongkan jika tidak diganti.</p>
                    </div>
                </template>
                <x-input-error :messages="$errors->get('qris_image')" />

                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-primary-container focus:ring-primary-container">
                    <span class="font-label-sm text-label-sm text-on-surface">Aktif (tampil di checkout)</span>
                </label>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="$dispatch('close-modal', 'payment-account-form')" class="rounded-lg bg-surface-container-low px-4 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container">Batal</button>
                    <button class="rounded-lg bg-primary px-4 py-2 font-label-md text-label-md text-on-primary shadow-sm hover:opacity-95" x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Simpan'"></button>
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
