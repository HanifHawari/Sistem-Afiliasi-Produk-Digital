@php
    use App\Enums\OrderStatus;
    use Illuminate\Support\Facades\URL;

    $card = 'rounded-xl border border-[#E2E8F0] bg-white p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] sm:p-space-lg';
@endphp

<x-layouts.shop :title="'Detail Pesanan '.$order->invoice_number">
    <div class="mb-space-md">
        <a href="{{ route('shop.orders.index') }}"
           class="inline-flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant transition hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Kembali ke Pesanan Saya</span>
        </a>
    </div>

    <x-page-header
        eyebrow="Detail Pesanan"
        :title="'Invoice '.$order->invoice_number"
        :description="'Dibuat pada '.$order->created_at->translatedFormat('d M Y, H:i')"
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
            <div>
                <p class="font-label-md text-label-md text-[#991B1B]">Unduhan Belum Tersedia</p>
                <p class="mt-0.5 font-body-sm text-body-sm text-[#DC2626]">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12">
        {{-- Kolom Kiri: Produk, Status & Tombol Aksi Sesuai Status --}}
        <section class="{{ $card }} lg:col-span-7">
            <h2 class="mb-space-md font-headline-sm text-headline-sm text-[#0F172A]">Informasi Produk</h2>

            <div class="flex flex-col gap-space-md rounded-xl bg-[#F8FAFC] p-space-md sm:flex-row sm:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-container text-primary">
                    @if ($order->product->thumbnail)
                        <img src="{{ asset('storage/'.$order->product->thumbnail) }}" alt="{{ $order->product->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-[32px]">inventory_2</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    @if ($order->product->category)
                        <span class="font-caption text-caption font-semibold uppercase tracking-wider text-primary">
                            {{ $order->product->category }}
                        </span>
                    @endif
                    <h3 class="font-headline-sm text-headline-sm text-[#0F172A]">
                        <a href="{{ route('shop.products.show', $order->product) }}" class="hover:text-primary hover:underline">
                            {{ $order->product->name }}
                        </a>
                    </h3>
                    <p class="tabular mt-1 font-currency-metric text-currency-metric text-primary">
                        {{ rupiah($order->amount) }}
                    </p>
                </div>
            </div>

            {{-- Panel Status & Tombol Aksi --}}
            @if ($order->status === OrderStatus::Pending)
                <div class="mt-space-lg rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-space-md">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-[#475569]">schedule</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-headline-sm text-headline-sm text-[#0F172A]">Menunggu Pembayaran</p>
                            <p class="mt-1 font-body-md text-body-md text-[#64748B]">
                                Pesanan kamu sudah tercatat. Silakan lakukan pembayaran dan unggah bukti bayar agar pesanan segera diproses.
                            </p>
                            <div class="mt-space-md">
                                <a href="{{ route('shop.orders.pay', $order) }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 font-label-md text-label-md text-white shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98] sm:w-auto">
                                    <span class="material-symbols-outlined text-[20px]">payments</span>
                                    <span>Bayar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($order->status === OrderStatus::WaitingVerification)
                <div class="mt-space-lg rounded-xl border border-[#FDE68A] bg-[#FFFBEB] p-space-md">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-[#D97706]">hourglass_top</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-headline-sm text-headline-sm text-[#92400E]">Menunggu Verifikasi Admin</p>
                            <p class="mt-1 font-body-md text-body-md text-[#B45309]">
                                Bukti pembayaran kamu sudah kami terima dan sedang diperiksa oleh admin. Setelah terverifikasi, tombol <strong>Download</strong> produk akan otomatis muncul di halaman ini.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif ($order->status === OrderStatus::Rejected)
                <div class="mt-space-lg rounded-xl border border-[#FECACA] bg-[#FEF2F2] p-space-md">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-[#DC2626]">error</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-headline-sm text-headline-sm text-[#991B1B]">Pembayaran Ditolak</p>
                            @if ($order->rejection_note)
                                <p class="mt-1 font-body-md text-body-md text-[#DC2626]">
                                    <strong>Alasan Penolakan:</strong> {{ $order->rejection_note }}
                                </p>
                            @else
                                <p class="mt-1 font-body-md text-body-md text-[#DC2626]">
                                    Bukti pembayaran belum sesuai. Silakan periksa kembali dan unggah ulang bukti pembayaran yang benar.
                                </p>
                            @endif
                            <div class="mt-space-md">
                                <a href="{{ route('shop.orders.pay', $order) }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 font-label-md text-label-md text-white shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98] sm:w-auto">
                                    <span class="material-symbols-outlined text-[20px]">upload_file</span>
                                    <span>Upload ulang bukti</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($order->isPaid())
                <div class="mt-space-lg rounded-xl border border-[#A7F3D0] bg-[#ECFDF5] p-space-md">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-[#059669]">verified</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-headline-sm text-headline-sm text-[#065F46]">Pembayaran Berhasil Diverifikasi</p>
                            <p class="mt-1 font-body-md text-body-md text-[#047857]">
                                Terima kasih! Pembayaran kamu telah disetujui. Silakan unduh file produk digital melalui tombol di bawah ini.
                            </p>
                            <div class="mt-space-md">
                                <a href="{{ URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $order]) }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#059669] px-5 py-3 font-label-md text-label-md text-white shadow-sm transition hover:bg-[#047857] focus:outline-none focus:ring-2 focus:ring-[#10B981] focus:ring-offset-2 active:scale-[0.98] sm:w-auto">
                                    <span class="material-symbols-outlined text-[20px]">download</span>
                                    <span>Download</span>
                                </a>
                                <p class="mt-2 font-caption text-caption text-[#047857]">
                                    Tautan unduhan aman berlaku selama 10 menit. Muat ulang halaman jika tautan kedaluwarsa.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- Kolom Kanan: Rincian Order --}}
        <aside class="{{ $card }} lg:sticky lg:top-24 lg:col-span-5">
            <h2 class="mb-space-md font-headline-sm text-headline-sm text-[#0F172A]">Rincian Pesanan</h2>

            <dl class="space-y-3 font-body-md text-body-md">
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-[#64748B]">Nomor Invoice</dt>
                    <dd class="tabular font-label-md text-label-md text-[#0F172A]">{{ $order->invoice_number }}</dd>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <dt class="text-[#64748B]">Status Pesanan</dt>
                    <dd>@include('shop.orders.partials.status-badge', ['status' => $order->status])</dd>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <dt class="text-[#64748B]">Tanggal Pembelian</dt>
                    <dd class="text-right text-[#0F172A]">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</dd>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <dt class="text-[#64748B]">Metode Pembayaran</dt>
                    <dd class="font-label-sm text-label-sm text-[#0F172A]">{{ $order->payment_method->label() }}</dd>
                </div>

                @if ($order->paymentAccount)
                    <div class="flex flex-col gap-0.5 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
                        <dt class="shrink-0 text-[#64748B]">Tujuan Pembayaran</dt>
                        <dd class="break-words font-label-sm text-label-sm text-[#0F172A] sm:text-right">{{ $order->paymentAccount->label() }}</dd>
                    </div>
                @endif

                @if ($order->verified_at)
                    <div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                        <dt class="text-[#64748B]">Waktu Verifikasi</dt>
                        <dd class="text-[#0F172A] sm:text-right">{{ $order->verified_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-2 border-t border-[#E2E8F0] pt-3">
                    <dt class="font-label-md text-label-md text-[#0F172A]">Total Nominal</dt>
                    <dd class="tabular font-currency-metric text-currency-metric text-primary">{{ rupiah($order->amount) }}</dd>
                </div>
            </dl>
        </aside>
    </div>

    <x-toast />
</x-layouts.shop>
