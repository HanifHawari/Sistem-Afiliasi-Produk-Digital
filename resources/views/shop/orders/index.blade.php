@php
    use App\Enums\OrderStatus;
    use Illuminate\Support\Facades\URL;
@endphp

<x-layouts.shop title="Pesanan Saya">
    <x-page-header
        eyebrow="Area Customer"
        title="Pesanan Saya"
        description="Pantau status pembayaran dan unduh produk digital yang sudah terverifikasi."
    >
        <x-slot:meta>{{ $orders->total() }} pesanan</x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('shop.products.index') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-[#E2E8F0] bg-white px-3.5 py-2 font-label-md text-label-md text-[#0F172A] shadow-sm transition hover:bg-[#F8FAFC]">
                <span class="material-symbols-outlined text-[18px] text-primary">storefront</span>
                <span>Belanja Produk Lain</span>
            </a>
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

    @if ($orders->isNotEmpty())
        {{-- Tampilan Mobile (< md / 360px): Daftar Kartu Responsif --}}
        <div class="flex flex-col gap-space-md md:hidden">
            @foreach ($orders as $order)
                <article class="rounded-xl border border-[#E2E8F0] bg-white p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)]">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-space-sm">
                        <div class="min-w-0">
                            <a href="{{ route('shop.orders.show', $order) }}" class="tabular font-label-md text-label-md text-primary hover:underline">
                                {{ $order->invoice_number }}
                            </a>
                            <p class="mt-0.5 font-caption text-caption text-[#64748B]">
                                {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>
                        @include('shop.orders.partials.status-badge', ['status' => $order->status])
                    </div>

                    <div class="py-space-sm">
                        <p class="line-clamp-2 font-headline-sm text-headline-sm text-[#0F172A]">
                            {{ $order->product->name }}
                        </p>
                        <div class="mt-2 flex items-baseline justify-between gap-2">
                            <span class="font-caption text-caption text-[#64748B]">{{ $order->payment_method->label() }}</span>
                            <span class="tabular font-label-md text-label-md font-bold text-[#0F172A]">{{ rupiah($order->amount) }}</span>
                        </div>

                        @if ($order->status === OrderStatus::Rejected && $order->rejection_note)
                            <div class="mt-2 rounded-lg border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 font-caption text-caption text-[#DC2626]">
                                <strong>Ditolak:</strong> {{ $order->rejection_note }}
                            </div>
                        @endif
                    </div>

                    <div class="mt-space-xs flex flex-col gap-2 border-t border-slate-100 pt-space-sm">
                        @if ($order->status === OrderStatus::Pending)
                            <a href="{{ route('shop.orders.pay', $order) }}"
                               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#4F46E5] px-4 py-2.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#4338CA] active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[16px]">payments</span>
                                <span>Bayar</span>
                            </a>
                        @elseif ($order->status === OrderStatus::Rejected)
                            <a href="{{ route('shop.orders.pay', $order) }}"
                               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#4F46E5] px-4 py-2.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#4338CA] active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[16px]">upload_file</span>
                                <span>Upload ulang bukti</span>
                            </a>
                        @elseif ($order->isPaid())
                            <a href="{{ URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $order]) }}"
                               class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#059669] px-4 py-2.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#047857] active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[16px]">download</span>
                                <span>Download</span>
                            </a>
                        @endif

                        <a href="{{ route('shop.orders.show', $order) }}"
                           class="inline-flex w-full items-center justify-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-4 py-2 font-label-sm text-label-sm text-[#0F172A] transition hover:bg-[#F8FAFC]">
                            <span>Lihat Detail</span>
                            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Tampilan Desktop (md+): Tabel Sesuai DESIGN.md --}}
        <div class="hidden overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] md:block">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] bg-[#F8FAFC] font-caption text-caption uppercase tracking-wider text-[#64748B]">
                            <th class="px-4 py-3.5 font-semibold">Invoice &amp; Tanggal</th>
                            <th class="px-4 py-3.5 font-semibold">Produk Digital</th>
                            <th class="px-4 py-3.5 font-semibold">Metode</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Total Bayar</th>
                            <th class="px-4 py-3.5 text-center font-semibold">Status</th>
                            <th class="px-4 py-3.5 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0] font-body-md text-body-md">
                        @foreach ($orders as $order)
                            <tr class="transition-colors hover:bg-[#F8FAFC]/60">
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <a href="{{ route('shop.orders.show', $order) }}" class="tabular font-label-md text-label-md text-primary hover:underline">
                                        {{ $order->invoice_number }}
                                    </a>
                                    <p class="mt-0.5 font-caption text-caption text-[#64748B]">
                                        {{ $order->created_at->translatedFormat('d M Y, H:i') }}
                                    </p>
                                </td>
                                <td class="max-w-xs px-4 py-3.5">
                                    <p class="line-clamp-1 font-label-md text-label-md text-[#0F172A]">
                                        {{ $order->product->name }}
                                    </p>
                                    @if ($order->status === OrderStatus::Rejected && $order->rejection_note)
                                        <p class="mt-0.5 line-clamp-1 font-caption text-caption text-[#DC2626]">
                                            Alasan ditolak: {{ $order->rejection_note }}
                                        </p>
                                    @elseif ($order->product->category)
                                        <p class="mt-0.5 font-caption text-caption text-[#64748B]">
                                            {{ $order->product->category }}
                                        </p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 font-body-sm text-body-sm text-[#475569]">
                                    {{ $order->payment_method->label() }}
                                </td>
                                <td class="tabular whitespace-nowrap px-4 py-3.5 text-right font-label-md text-label-md text-[#0F172A]">
                                    {{ rupiah($order->amount) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                    @include('shop.orders.partials.status-badge', ['status' => $order->status])
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        @if ($order->status === OrderStatus::Pending)
                                            <a href="{{ route('shop.orders.pay', $order) }}"
                                               class="inline-flex items-center gap-1 rounded-lg bg-[#4F46E5] px-3 py-1.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#4338CA]">
                                                <span class="material-symbols-outlined text-[15px]">payments</span>
                                                <span>Bayar</span>
                                            </a>
                                        @elseif ($order->status === OrderStatus::Rejected)
                                            <a href="{{ route('shop.orders.pay', $order) }}"
                                               class="inline-flex items-center gap-1 rounded-lg bg-[#4F46E5] px-3 py-1.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#4338CA]">
                                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                                <span>Upload ulang bukti</span>
                                            </a>
                                        @elseif ($order->isPaid())
                                            <a href="{{ URL::temporarySignedRoute('shop.orders.download', now()->addMinutes(10), ['order' => $order]) }}"
                                               class="inline-flex items-center gap-1 rounded-lg bg-[#059669] px-3 py-1.5 font-label-sm text-label-sm text-white shadow-sm transition hover:bg-[#047857]">
                                                <span class="material-symbols-outlined text-[15px]">download</span>
                                                <span>Download</span>
                                            </a>
                                        @endif

                                        <a href="{{ route('shop.orders.show', $order) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-[#E2E8F0] bg-white px-3 py-1.5 font-label-sm text-label-sm text-[#0F172A] transition hover:bg-[#F8FAFC]">
                                            <span>Detail</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginasi --}}
        @if ($orders->hasPages())
            <div class="mt-space-lg flex flex-col items-center justify-between gap-space-sm rounded-xl border border-[#E2E8F0] bg-white px-space-md py-3 sm:flex-row">
                <p class="font-caption text-caption text-[#64748B]">
                    Menampilkan <span class="tabular font-semibold text-[#0F172A]">{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</span>
                    dari <span class="tabular font-semibold text-[#0F172A]">{{ $orders->total() }}</span> pesanan
                </p>

                <div class="flex flex-wrap items-center gap-1">
                    @if ($orders->onFirstPage())
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 font-label-sm text-label-sm text-outline opacity-50">Sebelumnya</span>
                    @else
                        <a href="{{ $orders->previousPageUrl() }}" class="inline-flex items-center rounded-lg bg-surface-container-low px-2.5 py-1.5 font-label-sm text-label-sm text-on-surface hover:bg-surface-container">Sebelumnya</a>
                    @endif

                    @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                        <a href="{{ $url }}"
                           @class([
                               'tabular inline-flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 font-label-sm text-label-sm transition',
                               'bg-[#4F46E5] text-white shadow-sm' => $page === $orders->currentPage(),
                               'bg-surface-container-low text-on-surface-variant hover:bg-surface-container' => $page !== $orders->currentPage(),
                           ])>
                            {{ $page }}
                        </a>
                    @endforeach

                    @if ($orders->hasMorePages())
                        <a href="{{ $orders->nextPageUrl() }}" class="inline-flex items-center rounded-lg bg-surface-container-low px-2.5 py-1.5 font-label-sm text-label-sm text-on-surface hover:bg-surface-container">Berikutnya</a>
                    @else
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 font-label-sm text-label-sm text-outline opacity-50">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center rounded-xl border border-[#E2E8F0] bg-white px-space-md py-space-2xl text-center shadow-[0_1px_3px_rgba(30,27,75,0.04)]">
            <div class="mb-space-md flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-container-low text-primary">
                <span class="material-symbols-outlined text-[32px]">receipt_long</span>
            </div>
            <h2 class="font-headline-sm text-headline-sm text-[#0F172A]">Belum ada pesanan</h2>
            <p class="mt-1 max-w-md font-body-md text-body-md text-[#64748B]">
                Kamu belum memiliki riwayat transaksi produk digital. Yuk jelajahi katalog produk pilihan kami.
            </p>
            <a href="{{ route('shop.products.index') }}"
               class="mt-space-md inline-flex items-center gap-1.5 rounded-lg bg-[#4F46E5] px-4 py-2.5 font-label-md text-label-md text-white shadow-sm transition hover:bg-[#4338CA]">
                <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                <span>Lihat Katalog Produk</span>
            </a>
        </div>
    @endif

    <x-toast />
</x-layouts.shop>
