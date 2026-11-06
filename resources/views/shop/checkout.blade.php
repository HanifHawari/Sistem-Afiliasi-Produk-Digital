@php
    $user = auth()->user();
    $hasQris = (bool) $qrisAccount;
    $hasBank = $bankAccounts->isNotEmpty();
    $defaultMethod = old('payment_method', $hasQris ? 'qris' : ($hasBank ? 'bank_transfer' : ''));
    $defaultAccount = (string) old('payment_account_id', $bankAccounts->first()?->id);
    $card = 'rounded-xl border border-slate-200 bg-surface-container-lowest p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] sm:p-space-lg';
    $step = 'flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-container font-label-md text-label-md text-on-primary';
@endphp

<x-layouts.shop title="Checkout & Pembayaran">
    <a href="{{ route('shop.products.show', $product) }}"
       class="mb-space-md inline-flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant transition hover:text-primary">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Kembali ke detail produk</span>
    </a>

    <x-page-header eyebrow="Checkout" title="Checkout & Pembayaran" description="Periksa pesanan, pilih metode pembayaran, lalu lanjutkan ke instruksi bayar." />

    @if (session('error'))
        <div class="mb-space-lg flex items-start gap-3 rounded-xl border border-[#FECACA] bg-[#FEF2F2] p-space-md text-[#DC2626]">
            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[20px]">error</span>
            <p class="font-body-md text-body-md">{{ session('error') }}</p>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-space-lg flex items-start gap-3 rounded-xl border border-[#A7F3D0] bg-[#ECFDF5] p-space-md text-[#059669]">
            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[20px]">check_circle</span>
            <div>
                <p class="font-label-md text-label-md text-[#002113]">{{ session('status') }}</p>
                @if (session('status_detail'))
                    <p class="mt-0.5 font-body-sm text-body-sm text-[#005236]">{{ session('status_detail') }}</p>
                @endif
            </div>
        </div>
    @endif

    @if ($pendingOrder)
        <div class="mb-space-lg flex flex-col gap-space-sm rounded-xl border border-amber-200 bg-amber-50 p-space-md sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-2.5">
                <span class="material-symbols-outlined shrink-0 text-[20px] text-amber-600">info</span>
                <p class="font-body-md text-body-md text-amber-900">
                    Kamu masih punya pesanan <strong class="tabular">{{ $pendingOrder->invoice_number }}</strong> yang belum dibayar untuk produk ini.
                    Melanjutkan checkout akan memakai pesanan tersebut.
                </p>
            </div>
            <a href="{{ route('shop.orders.pay', $pendingOrder) }}"
               class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg border border-amber-300 bg-white px-3 py-2 font-label-sm text-label-sm text-amber-800 hover:bg-amber-100">
                Lihat instruksi bayar
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('shop.checkout.store', $product) }}"
          x-data="{ method: @js($defaultMethod), account: @js($defaultAccount), submitting: false }"
          @submit="submitting = true"
          class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12">
        @csrf

        <div class="flex min-w-0 flex-col gap-space-lg lg:col-span-7">
            {{-- 1. Data pembeli --}}
            <section class="{{ $card }}">
                <div class="mb-space-md flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="{{ $step }}">1</span>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Data Pembeli</h2>
                    </div>
                    <span class="inline-flex items-center gap-1 font-caption text-caption text-secondary">
                        <span class="material-symbols-outlined text-[14px]">lock</span> Data akun kamu
                    </span>
                </div>

                <dl class="grid grid-cols-1 gap-space-sm sm:grid-cols-2">
                    <div class="min-w-0 rounded-lg bg-surface-container-low px-3 py-2.5">
                        <dt class="font-caption text-caption text-on-surface-variant">Nama</dt>
                        <dd class="truncate font-label-md text-label-md text-on-surface">{{ $user->name }}</dd>
                    </div>
                    <div class="min-w-0 rounded-lg bg-surface-container-low px-3 py-2.5">
                        <dt class="font-caption text-caption text-on-surface-variant">Email</dt>
                        <dd class="truncate font-label-md text-label-md text-on-surface">{{ $user->email }}</dd>
                    </div>
                    <div class="min-w-0 rounded-lg bg-surface-container-low px-3 py-2.5 sm:col-span-2">
                        <dt class="font-caption text-caption text-on-surface-variant">No. WhatsApp</dt>
                        <dd class="truncate font-label-md text-label-md text-on-surface">{{ $user->phone ?: '-' }}</dd>
                    </div>
                </dl>
                <p class="mt-space-sm font-caption text-caption text-on-surface-variant">
                    Ingin mengubah data? Buka <a href="{{ route('profile.edit') }}" class="text-primary hover:underline">Profil</a>.
                </p>
            </section>

            {{-- 2. Metode pembayaran --}}
            <section class="{{ $card }}">
                <div class="mb-space-md flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="{{ $step }}">2</span>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Pilih Metode Pembayaran</h2>
                    </div>
                    <span class="font-caption text-caption text-on-surface-variant">Biaya admin: Gratis</span>
                </div>

                @if (! $hasQris && ! $hasBank)
                    <div class="flex items-start gap-2.5 rounded-lg bg-error-container p-space-md text-on-error-container">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">error</span>
                        <p class="font-body-md text-body-md">Metode pembayaran belum tersedia. Silakan hubungi admin.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-space-sm sm:grid-cols-2">
                        @if ($hasQris)
                            <label class="relative cursor-pointer rounded-xl border p-space-md transition"
                                   :class="method === 'qris' ? 'border-2 border-primary-container bg-primary-fixed/40' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="payment_method" value="qris" x-model="method" class="sr-only">
                                <span x-show="method === 'qris'" x-cloak class="material-symbols-outlined absolute right-3 top-3 text-[20px] text-primary-container">verified</span>
                                <span class="flex items-center gap-2 font-headline-sm text-headline-sm text-on-surface">
                                    <span class="material-symbols-outlined text-primary">qr_code_2</span> QRIS
                                </span>
                                <span class="mt-1 block font-body-sm text-body-sm text-on-surface-variant">GoPay, OVO, DANA, ShopeePay, LinkAja &amp; m-banking.</span>
                            </label>
                        @endif

                        @if ($hasBank)
                            <label class="relative cursor-pointer rounded-xl border p-space-md transition"
                                   :class="method === 'bank_transfer' ? 'border-2 border-primary-container bg-primary-fixed/40' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="payment_method" value="bank_transfer" x-model="method" class="sr-only">
                                <span x-show="method === 'bank_transfer'" x-cloak class="material-symbols-outlined absolute right-3 top-3 text-[20px] text-primary-container">verified</span>
                                <span class="flex items-center gap-2 font-headline-sm text-headline-sm text-on-surface">
                                    <span class="material-symbols-outlined text-primary">account_balance</span> Transfer Bank
                                </span>
                                <span class="mt-1 block font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $bankAccounts->pluck('bank_name')->filter()->unique()->join(', ') }}. Verifikasi manual oleh admin.
                                </span>
                            </label>
                        @endif
                    </div>

                    {{-- Pilih rekening tujuan (transfer bank) --}}
                    @if ($hasBank)
                        <div x-show="method === 'bank_transfer'" x-cloak class="mt-space-md">
                            <p class="mb-2 font-label-sm text-label-sm text-on-surface">Rekening tujuan</p>
                            <div class="grid grid-cols-1 gap-space-sm sm:grid-cols-2">
                                @foreach ($bankAccounts as $account)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition"
                                           :class="account === '{{ $account->id }}' ? 'border-2 border-primary-container' : 'border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" name="payment_account_id" value="{{ $account->id }}" x-model="account"
                                               :disabled="method !== 'bank_transfer'"
                                               class="text-primary-container focus:ring-primary-container">
                                        <span class="min-w-0">
                                            <span class="block font-label-md text-label-md text-on-surface">{{ $account->bank_name }}</span>
                                            <span class="block truncate font-caption text-caption text-on-surface-variant">a.n. {{ $account->account_holder }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('payment_account_id')" class="mt-1" />
                        </div>
                    @endif

                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                @endif
            </section>

            {{-- 3. Kode referral --}}
            <section class="{{ $card }}">
                <div class="mb-space-md flex items-center gap-2.5">
                    <span class="{{ $step }}">3</span>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Kode Referral <span class="font-body-md text-body-md text-on-surface-variant">(opsional)</span></h2>
                </div>
                <label for="referral_code" class="sr-only">Kode referral</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline">confirmation_number</span>
                    <input id="referral_code" name="referral_code" type="text" maxlength="50" autocomplete="off"
                           value="{{ $referralCode }}" placeholder="Contoh: GIBRAN1"
                           class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 font-body-md text-body-md uppercase text-on-surface placeholder:normal-case placeholder:text-slate-400 focus:border-primary-container focus:ring-2 focus:ring-primary-container">
                </div>
                <p class="mt-1.5 font-caption text-caption text-on-surface-variant">
                    @if ($referralCode !== '')
                        Terisi otomatis dari link afiliator yang kamu kunjungi.
                    @else
                        Masukkan kode dari afiliator yang merekomendasikan produk ini, jika ada.
                    @endif
                </p>
            </section>
        </div>

        {{-- Ringkasan pesanan --}}
        <aside class="lg:sticky lg:top-24 lg:col-span-5">
            <div class="{{ $card }}">
                <p class="mb-space-md font-caption text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Ringkasan Pesanan</p>

                <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-3">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-container text-primary">
                        @if ($product->thumbnail)
                            <img src="{{ asset('storage/'.$product->thumbnail) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-[28px]">inventory_2</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        @if ($product->category)
                            <p class="font-caption text-caption font-semibold uppercase tracking-wider text-primary">{{ $product->category }}</p>
                        @endif
                        <p class="line-clamp-2 font-label-md text-label-md text-on-surface">{{ $product->name }}</p>
                    </div>
                </div>

                <dl class="mt-space-md space-y-2 border-b border-slate-100 pb-space-md font-body-md text-body-md">
                    <div class="flex justify-between gap-2">
                        <dt class="text-on-surface-variant">Harga produk</dt>
                        <dd class="tabular text-on-surface">{{ rupiah($product->price) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-on-surface-variant">Biaya admin</dt>
                        <dd class="tabular text-secondary">Gratis</dd>
                    </div>
                </dl>

                <div class="flex items-end justify-between gap-2 pt-space-md">
                    <span class="font-label-md text-label-md text-on-surface">Total Pembayaran</span>
                    <span class="tabular font-currency-metric text-currency-metric text-primary">{{ rupiah($product->price) }}</span>
                </div>

                <button type="submit"
                        @disabled(! $hasQris && ! $hasBank)
                        :disabled="submitting || {{ (! $hasQris && ! $hasBank) ? 'true' : 'false' }}"
                        class="mt-space-lg inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-3 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="material-symbols-outlined text-[20px]" x-text="submitting ? 'progress_activity' : 'lock'">lock</span>
                    <span x-text="submitting ? 'Memproses Pesanan...' : 'Lanjut ke Pembayaran'">Lanjut ke Pembayaran</span>
                </button>
                <p class="mt-space-sm text-center font-caption text-caption text-on-surface-variant">
                    Produk bisa diunduh setelah pembayaran diverifikasi admin.
                </p>
            </div>
        </aside>
    </form>

    <x-toast />
</x-layouts.shop>
