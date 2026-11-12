@php
    $account = $order->paymentAccount;
    $isQris = $order->payment_method === \App\Enums\PaymentMethod::Qris;
    $isPending = $order->status === \App\Enums\OrderStatus::Pending;
    $isRejected = $order->status === \App\Enums\OrderStatus::Rejected;
    $isWaiting = $order->status === \App\Enums\OrderStatus::WaitingVerification;
    $canUploadProof = $isPending || $isRejected;
    $card = 'rounded-xl border border-slate-200 bg-surface-container-lowest p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] sm:p-space-lg';
@endphp

<x-layouts.shop :title="'Pembayaran '.$order->invoice_number">
    <div class="mb-space-md">
        <a href="{{ route('shop.orders.index') }}"
           class="inline-flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant transition hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Kembali ke Pesanan Saya</span>
        </a>
    </div>

    <x-page-header
        eyebrow="Pembayaran Pesanan"
        :title="'Invoice '.$order->invoice_number"
        :description="'Produk: '.$order->product->name"
    >
        <x-slot:actions>
            @include('shop.orders.partials.status-badge', ['status' => $order->status])
        </x-slot:actions>
    </x-page-header>

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

    @if (session('error'))
        <div class="mb-space-lg flex items-start gap-3 rounded-xl border border-[#FECACA] bg-[#FEF2F2] p-space-md text-[#DC2626]">
            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[20px]">error</span>
            <p class="font-body-md text-body-md">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Banner Penolakan jika status = rejected --}}
    @if ($isRejected)
        <div class="mb-space-lg flex items-start gap-3 rounded-xl border border-red-200 bg-[#FEF2F2] p-space-md text-[#DC2626]">
            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[22px]">error</span>
            <div class="min-w-0 flex-1">
                <p class="font-headline-sm text-headline-sm text-[#93000a]">Bukti Pembayaran Ditolak</p>
                @if ($order->rejection_note)
                    <p class="mt-1 font-body-md text-body-md text-[#93000a]">
                        <strong>Catatan Admin:</strong> {{ $order->rejection_note }}
                    </p>
                @else
                    <p class="mt-1 font-body-md text-body-md text-[#93000a]">
                        Bukti pembayaran sebelumnya belum sesuai. Silakan unggah ulang bukti transfer yang valid.
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12"
         x-data="{
            copied: null,
            fileName: '',
            copy(text, key) {
                navigator.clipboard?.writeText(text);
                this.copied = key;
                setTimeout(() => { if (this.copied === key) this.copied = null; }, 1800);
            }
         }">

        {{-- Kolom Kiri: Nominal Besar, Instruksi Pembayaran & Form Upload / Status Verifikasi --}}
        <div class="flex min-w-0 flex-col gap-space-lg lg:col-span-7">
            {{-- Kartu Nominal & Instruksi --}}
            <section class="{{ $card }}">
                <div class="mb-space-md flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-space-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">{{ $isQris ? 'qr_code_2' : 'account_balance' }}</span>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">
                            Instruksi {{ $order->payment_method->label() }}
                        </h2>
                    </div>
                    <span class="tabular font-label-sm text-label-sm text-on-surface-variant">{{ $order->invoice_number }}</span>
                </div>

                {{-- Nominal Besar --}}
                <div class="mb-space-lg rounded-xl bg-surface-container-low p-space-md sm:p-space-lg">
                    <span class="block font-caption text-caption font-semibold uppercase tracking-wider text-on-surface-variant">
                        Total Nominal Pembayaran
                    </span>
                    <div class="mt-1 flex flex-col gap-space-sm sm:flex-row sm:items-center sm:justify-between">
                        <span class="tabular break-all font-currency-display text-[1.75rem] leading-tight text-primary sm:text-currency-display">
                            {{ rupiah($order->amount) }}
                        </span>
                        <button type="button"
                                @click="copy('{{ $order->amount }}', 'amount')"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-surface-container-lowest px-3 py-2 font-label-sm text-label-sm text-on-surface shadow-sm transition hover:bg-slate-50 sm:w-auto">
                            <span class="material-symbols-outlined text-[16px] text-primary" x-text="copied === 'amount' ? 'check' : 'content_copy'">content_copy</span>
                            <span x-text="copied === 'amount' ? 'Nominal Tersalin!' : 'Salin Nominal'">Salin Nominal</span>
                        </button>
                    </div>
                    <p class="mt-1.5 font-caption text-caption text-on-surface-variant">
                        Pastikan membayar sesuai nominal tepat di atas agar verifikasi berjalan cepat.
                    </p>
                </div>

                {{-- Detail QRIS atau Rekening Bank --}}
                @if (! $account)
                    <div class="flex items-start gap-2.5 rounded-lg bg-error-container p-space-md text-on-error-container">
                        <span class="material-symbols-outlined shrink-0 text-[20px]">warning</span>
                        <p class="font-body-md text-body-md">
                            Akun tujuan pembayaran untuk metode ini sedang tidak tersedia. Silakan ganti metode pembayaran atau hubungi admin.
                        </p>
                    </div>
                @elseif ($isQris)
                    <div class="mx-auto flex max-w-xs flex-col items-center rounded-xl border border-slate-200 bg-white p-space-md shadow-sm">
                        <div class="mb-2 flex w-full items-center justify-between border-b border-slate-100 pb-2">
                            <span class="font-headline-sm text-headline-sm text-on-surface">QRIS</span>
                            <span class="font-caption text-caption text-on-surface-variant">Standar Nasional</span>
                        </div>

                        @if ($account->qrisImageUrl())
                            <img src="{{ $account->qrisImageUrl() }}"
                                 alt="QRIS {{ $account->account_holder }}"
                                 class="aspect-square w-full max-w-[240px] rounded-lg object-contain">
                        @else
                            <div class="flex aspect-square w-full max-w-[240px] flex-col items-center justify-center gap-2 rounded-lg bg-surface-container-low p-space-md text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[36px] text-primary">qr_code_2</span>
                                <span class="font-body-sm text-body-sm">Gambar QRIS belum tersedia</span>
                            </div>
                        @endif

                        <div class="mt-3 w-full rounded-lg bg-surface-container-low px-3 py-2 text-center">
                            <p class="font-caption text-caption text-on-surface-variant">Merchant Resmi</p>
                            <p class="font-label-md text-label-md text-primary">{{ $account->account_holder }}</p>
                        </div>
                    </div>

                    <p class="mt-space-md text-center font-body-sm text-body-sm text-on-surface-variant">
                        Buka aplikasi m-banking atau e-wallet (GoPay, OVO, DANA, ShopeePay, LinkAja), pilih menu <strong>Scan QRIS</strong>, lalu arahkan kamera ke kode QR di atas.
                    </p>
                @else
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <dl class="divide-y divide-slate-100">
                            <div class="flex flex-col gap-1 p-space-md sm:flex-row sm:items-center sm:justify-between">
                                <dt class="font-body-sm text-body-sm text-on-surface-variant">Bank Tujuan</dt>
                                <dd class="inline-flex items-center gap-2 font-headline-sm text-headline-sm text-on-surface">
                                    <span class="rounded bg-primary-fixed px-2.5 py-0.5 font-label-sm text-label-sm text-primary">{{ $account->bank_name }}</span>
                                </dd>
                            </div>

                            <div class="flex flex-col gap-2 p-space-md sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <dt class="font-body-sm text-body-sm text-on-surface-variant">Nomor Rekening</dt>
                                    <dd class="tabular mt-0.5 font-headline-md text-headline-md tracking-wide text-on-surface">
                                        {{ $account->account_number }}
                                    </dd>
                                </div>
                                <button type="button"
                                        @click="copy('{{ $account->account_number }}', 'account_number')"
                                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-surface-container-low px-3.5 py-2 font-label-sm text-label-sm text-primary transition hover:bg-primary-fixed sm:w-auto">
                                    <span class="material-symbols-outlined text-[16px]" x-text="copied === 'account_number' ? 'check' : 'content_copy'">content_copy</span>
                                    <span x-text="copied === 'account_number' ? 'Tersalin!' : 'Salin Nomor'">Salin Nomor</span>
                                </button>
                            </div>

                            <div class="flex flex-col gap-1 p-space-md sm:flex-row sm:items-center sm:justify-between">
                                <dt class="font-body-sm text-body-sm text-on-surface-variant">Atas Nama</dt>
                                <dd class="font-label-md text-label-md text-on-surface">{{ $account->account_holder }}</dd>
                            </div>
                        </dl>
                    </div>

                    <p class="mt-space-sm font-body-sm text-body-sm text-on-surface-variant">
                        Cantumkan nomor invoice <strong class="tabular text-on-surface">{{ $order->invoice_number }}</strong> pada berita/catatan transfer.
                    </p>
                @endif
            </section>

            {{-- Bagian Status Menunggu Verifikasi ATAU Form Upload Bukti --}}
            @if ($isWaiting)
                <section class="rounded-xl border border-amber-200 bg-[#FFFBEB] p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04)] sm:p-space-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 rounded-xl bg-amber-100 p-2.5 text-[24px] text-[#D97706]">hourglass_top</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-headline-sm text-headline-sm text-amber-950">Menunggu Verifikasi Pembayaran</h2>
                                @include('shop.orders.partials.status-badge', ['status' => $order->status])
                            </div>
                            <p class="mt-1.5 font-body-md text-body-md text-amber-900">
                                Bukti pembayaran kamu sudah kami terima dan sedang diperiksa oleh tim admin. Setelah pembayaran disetujui, tombol unduh produk akan otomatis aktif di halaman pesanan.
                            </p>
                            <div class="mt-space-md flex flex-wrap items-center gap-space-sm">
                                <a href="{{ route('shop.orders.show', $order) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-primary-container px-4 py-2 font-label-sm text-label-sm text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600">
                                    <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                                    <span>Lihat Detail Pesanan</span>
                                </a>
                                <a href="{{ route('shop.orders.index') }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-4 py-2 font-label-sm text-label-sm text-amber-900 transition hover:bg-amber-100">
                                    <span>Ke Pesanan Saya</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @elseif ($canUploadProof)
                <section class="{{ $card }}">
                    <div class="mb-space-md flex items-center gap-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-container font-label-md text-label-md text-on-primary">
                            <span class="material-symbols-outlined text-[16px]">upload_file</span>
                        </span>
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                                {{ $isRejected ? 'Unggah Ulang Bukti Pembayaran' : 'Unggah Bukti Pembayaran' }}
                            </h2>
                            <p class="font-caption text-caption text-on-surface-variant">
                                Unggah tangkapan layar (screenshot) atau struk bukti transfer untuk diverifikasi admin.
                            </p>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('shop.orders.proof', $order) }}"
                          enctype="multipart/form-data"
                          x-data="{ submitting: false }"
                          @submit="submitting = true"
                          class="space-y-space-md">
                        @csrf

                        <div>
                            <label for="payment_proof"
                                   class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-surface-container-low/70 px-space-md py-space-lg text-center transition hover:border-primary-container hover:bg-surface-container-low">
                                <span class="material-symbols-outlined mb-2 rounded-full bg-primary-fixed p-3 text-[28px] text-primary">cloud_upload</span>
                                <span class="break-all font-label-md text-label-md text-on-surface" x-text="fileName || 'Klik untuk memilih file bukti bayar'">
                                    Klik untuk memilih file bukti bayar
                                </span>
                                <span class="mt-1 font-caption text-caption text-on-surface-variant">
                                    Format JPG, JPEG, PNG, atau PDF (Maksimal 2 MB)
                                </span>
                                <input id="payment_proof"
                                       name="payment_proof"
                                       type="file"
                                       accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                       required
                                       class="sr-only"
                                       @change="fileName = $event.target.files[0]?.name || ''">
                            </label>
                            <x-input-error :messages="$errors->get('payment_proof')" class="mt-1.5" />
                        </div>

                        <button type="submit"
                                :disabled="submitting"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-3 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                            <span class="material-symbols-outlined text-[20px]" x-text="submitting ? 'progress_activity' : 'check_circle'">check_circle</span>
                            <span x-text="submitting ? 'Mengunggah Bukti...' : '{{ $isRejected ? 'Kirim Ulang Bukti Pembayaran' : 'Konfirmasi & Kirim Bukti Pembayaran' }}'">
                                {{ $isRejected ? 'Kirim Ulang Bukti Pembayaran' : 'Konfirmasi & Kirim Bukti Pembayaran' }}
                            </span>
                        </button>
                    </form>
                </section>
            @endif
        </div>

        {{-- Kolom Kanan: Ringkasan Pesanan & Produk --}}
        <aside class="{{ $card }} lg:sticky lg:top-24 lg:col-span-5">
            <p class="mb-space-md font-caption text-caption font-semibold uppercase tracking-wider text-on-surface-variant">
                Ringkasan Pesanan
            </p>

            <div class="flex items-center gap-3 rounded-xl bg-surface-container-low p-3">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-container text-primary">
                    @if ($order->product->thumbnail)
                        <img src="{{ asset('storage/'.$order->product->thumbnail) }}" alt="{{ $order->product->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-[28px]">inventory_2</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    @if ($order->product->category)
                        <p class="font-caption text-caption font-semibold uppercase tracking-wider text-primary">
                            {{ $order->product->category }}
                        </p>
                    @endif
                    <h3 class="line-clamp-2 font-label-md text-label-md text-on-surface">
                        {{ $order->product->name }}
                    </h3>
                </div>
            </div>

            <dl class="mt-space-md space-y-2.5 border-t border-slate-100 pt-space-md font-body-md text-body-md">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-on-surface-variant">Nomor Invoice</dt>
                    <dd class="tabular font-label-md text-label-md text-on-surface">{{ $order->invoice_number }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-on-surface-variant">Waktu Pesanan</dt>
                    <dd class="text-right text-on-surface">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-on-surface-variant">Metode Bayar</dt>
                    <dd class="font-label-sm text-label-sm text-on-surface">{{ $order->payment_method->label() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-on-surface-variant">Status</dt>
                    <dd>@include('shop.orders.partials.status-badge', ['status' => $order->status])</dd>
                </div>
                <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-space-sm">
                    <dt class="font-label-md text-label-md text-on-surface">Total Bayar</dt>
                    <dd class="tabular font-currency-metric text-currency-metric text-primary">{{ rupiah($order->amount) }}</dd>
                </div>
            </dl>

            <div class="mt-space-lg flex flex-col gap-space-sm">
                @if ($isPending)
                    <a href="{{ route('shop.checkout', $order->product) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 font-label-md text-label-md text-slate-900 transition hover:bg-slate-50">
                        <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                        <span>Ganti Metode Pembayaran</span>
                    </a>
                @endif

                <a href="{{ route('shop.orders.index') }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 font-label-md text-label-md text-primary transition hover:bg-primary-fixed/50">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    <span>Lihat Semua Pesanan</span>
                </a>
            </div>
        </aside>
    </div>

    <x-toast />
</x-layouts.shop>
