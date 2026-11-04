@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentAccountType')

@php
    $tabs = [
        'all' => ['Semua Transaksi', $counts->sum()],
        OrderStatus::WaitingVerification->value => ['Menunggu Verifikasi', $counts[OrderStatus::WaitingVerification->value] ?? 0],
        OrderStatus::Pending->value => ['Belum Bayar', $counts[OrderStatus::Pending->value] ?? 0],
        OrderStatus::Paid->value => ['Berhasil', $counts[OrderStatus::Paid->value] ?? 0],
        OrderStatus::Rejected->value => ['Ditolak', $counts[OrderStatus::Rejected->value] ?? 0],
    ];

    $accountLabel = fn ($account) => match (true) {
        $account === null => '-',
        $account->type === PaymentAccountType::Qris => 'QRIS · '.$account->account_holder,
        default => "{$account->bank_name} {$account->account_number} (a.n. {$account->account_holder})",
    };
@endphp

<x-layouts.admin title="Verifikasi Pembayaran">
    <x-page-header eyebrow="Transaksi" title="Verifikasi Pembayaran"
        description="Tinjau bukti pembayaran transfer/QRIS, validasi transaksi, lalu buka akses produk digital untuk customer.">
        @if ($stats['waiting_count'] > 0)
            <x-slot name="meta">{{ $stats['waiting_count'] }} transaksi butuh tindakan</x-slot>
        @endif
        <x-slot name="actions">
            <a href="{{ route('admin.payment-accounts.index') }}" class="flex items-center gap-space-xs rounded-lg bg-primary-container px-space-md py-2.5 text-on-primary shadow-sm transition-all hover:bg-primary">
                <span class="material-symbols-outlined text-[18px]">account_balance</span>
                <span class="font-label-md text-label-md">Rekening &amp; QRIS</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- Statistik --}}
    <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
        <x-stat-card label="Volume Terverifikasi Hari Ini" icon="payments" :value="rupiah($stats['volume_today'])" caption="Total pembayaran disetujui hari ini" />
        <x-stat-card label="Menunggu Verifikasi" icon="pending_actions" tone="tertiary" :value="$stats['waiting_count'].' pesanan'">
            @if ($stats['waiting_oldest'])
                <span class="flex items-center gap-1 text-error">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    Tertua {{ $stats['waiting_oldest']->diffForHumans() }}
                </span>
            @else
                <span class="flex items-center gap-1 text-secondary">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Tidak ada antrean
                </span>
            @endif
        </x-stat-card>
        <x-stat-card label="Komisi Terkredit Bulan Ini" icon="handshake" tone="secondary" value-class="text-secondary" :value="rupiah($stats['commission_month'])" caption="Otomatis saat pembayaran disetujui" />
        <x-stat-card label="Transaksi Berhasil" icon="task_alt" :value="$stats['paid_count'].' pesanan'" caption="Akses produk sudah terbuka" />
    </div>

    {{-- Tab status + pencarian --}}
    <div class="mb-space-lg flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-surface-container-low p-1.5">
        <div class="no-scrollbar flex w-full items-center gap-1 overflow-x-auto sm:w-auto sm:flex-wrap">
            @foreach ($tabs as $key => [$label, $total])
                <a href="{{ route('admin.orders.index', array_filter(['status' => $key, 'q' => $search])) }}"
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
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex w-full items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-sm py-1 sm:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined text-[18px] text-outline">search</span>
            <input name="q" value="{{ $search }}" type="text" placeholder="Cari invoice, pembeli, kode ref..."
                   class="w-full border-0 bg-transparent p-1 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:ring-0 sm:w-60">
        </form>
    </div>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Tabel transaksi --}}
        <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-8">
            <div class="overflow-x-auto">
                <table class="ledger w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-3 py-3">Transaksi &amp; Status</th>
                            <th class="px-3 py-3">Customer, Produk &amp; Afiliator</th>
                            <th class="px-3 py-3 text-right">Nominal Total</th>
                            <th class="px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-body-md text-body-md">
                        @forelse ($orders as $order)
                            @php
                                $isSelected = $selected?->is($order);
                                $rate = $order->commission?->rate ?? $order->product->commission_rate;
                                $commissionAmount = $order->referralLink ? ($order->commission?->amount ?? (int) round($order->amount * $rate / 100)) : 0;
                                $selectUrl = route('admin.orders.index', array_filter(['status' => $status, 'q' => $search, 'page' => $orders->currentPage(), 'order' => $order->id]));
                            @endphp
                            <tr @class([
                                'transition-colors',
                                'bg-primary-fixed/30 hover:bg-primary-fixed/40' => $isSelected,
                                'hover:bg-surface-container-low' => ! $isSelected,
                            ])>
                                <td class="whitespace-nowrap px-3 py-3.5 align-top">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ $selectUrl }}" @class(['font-label-md text-label-md hover:underline', 'font-bold text-primary' => $isSelected, 'text-on-surface' => ! $isSelected])>#{{ $order->invoice_number }}</a>
                                        <span @class([
                                            'rounded px-1.5 font-caption text-caption',
                                            'bg-secondary-fixed text-on-secondary-fixed' => $order->payment_method->value === 'qris',
                                            'bg-surface-container-highest text-tertiary' => $order->payment_method->value !== 'qris',
                                        ])>{{ $order->paymentAccount?->bank_name ?? $order->payment_method->label() }}</span>
                                    </div>
                                    <span class="mb-1.5 mt-0.5 block font-caption text-caption text-on-surface-variant">{{ $order->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                    <x-badge :tone="$order->status->tone()" :pulse="$order->status === OrderStatus::WaitingVerification">{{ $order->status->label() }}</x-badge>
                                </td>
                                <td class="min-w-[200px] px-3 py-3.5 align-top">
                                    <span class="block font-label-md text-label-md text-on-surface">{{ $order->customer->name }}</span>
                                    <span class="block font-caption text-caption text-on-surface-variant">
                                        {{ $order->customer->email }}{{ $order->customer->phone ? ' · '.$order->customer->phone : '' }}
                                    </span>
                                    <span class="mt-1.5 line-clamp-2 font-label-md text-label-md text-on-surface">{{ $order->product->name }}</span>
                                    @if ($order->referralLink)
                                        <div class="mt-1 flex flex-wrap items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px] text-primary">person</span>
                                            <span class="font-caption text-caption font-medium text-primary">{{ $order->referralLink->affiliate->name }}</span>
                                            <span class="rounded-full bg-secondary-fixed px-1.5 font-caption text-[10px] text-on-secondary-fixed">ref: {{ $order->referralLink->code }}</span>
                                        </div>
                                    @else
                                        <span class="mt-1 block font-caption text-caption text-outline">Organik (tanpa afiliator)</span>
                                    @endif
                                </td>
                                <td class="tabular whitespace-nowrap px-3 py-3.5 text-right align-top">
                                    <div class="font-label-md text-label-md font-bold text-on-surface">{{ rupiah($order->amount) }}</div>
                                    @if ($commissionAmount)
                                        <div class="mt-0.5 font-caption text-caption text-secondary">Komisi: {{ rupiah($commissionAmount) }} ({{ (float) $rate }}%)</div>
                                    @else
                                        <div class="mt-0.5 font-caption text-caption text-outline">Tanpa afiliasi</div>
                                    @endif
                                    <div class="font-caption text-caption text-on-surface-variant">Net Owner: {{ rupiah($order->amount - $commissionAmount) }}</div>
                                </td>
                                <td class="px-3 py-3.5 text-right align-top">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (in_array($order->status, [OrderStatus::Pending, OrderStatus::WaitingVerification]))
                                            <form method="POST" action="{{ route('admin.orders.approve', $order) }}" onsubmit="return confirm({{ Js::from('Setujui pembayaran '.$order->invoice_number.'?') }})">
                                                @csrf
                                                <button class="rounded-lg bg-secondary p-1.5 text-on-secondary shadow-sm transition-colors hover:bg-secondary/90" title="Setujui &amp; buka akses">
                                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ $selectUrl }}" class="rounded-lg bg-surface-container-high p-1.5 text-on-surface-variant transition-colors hover:bg-surface-container-highest" title="Periksa detail &amp; bukti bayar">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-space-2xl text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined mb-1 block text-[36px] text-outline">receipt_long</span>
                                    Tidak ada transaksi{{ $search ? ' untuk pencarian "'.$search.'"' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between bg-surface-container-low px-space-md py-3">
                <span class="font-caption text-caption text-on-surface-variant">
                    Menampilkan {{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} transaksi
                </span>
                <div class="flex items-center gap-1">
                    <a href="{{ $orders->previousPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => $orders->onFirstPage()])>
                        <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                    </a>
                    <span class="px-2 font-label-sm text-label-sm text-on-surface">Hal {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}</span>
                    <a href="{{ $orders->nextPageUrl() ?? '#' }}" @class(['flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-high', 'pointer-events-none opacity-50' => ! $orders->hasMorePages()])>
                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Panel inspeksi --}}
        <div class="flex flex-col gap-space-md xl:col-span-4">
            @if ($selected)
                @php
                    $canVerify = in_array($selected->status, [OrderStatus::Pending, OrderStatus::WaitingVerification]);
                    $rate = $selected->commission?->rate ?? $selected->product->commission_rate;
                    $commissionAmount = $selected->referralLink ? ($selected->commission?->amount ?? (int) round($selected->amount * $rate / 100)) : 0;
                    $proofIsImage = $selected->payment_proof && in_array(strtolower(pathinfo($selected->payment_proof, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                @endphp
                <div id="inspeksi" class="flex scroll-mt-20 flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-md shadow-md" x-init="if (new URLSearchParams(location.search).has('order') && innerWidth < 1280) $el.scrollIntoView()"
                     x-data="{ rejecting: {{ $errors->has('rejection_note') ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span @class(['h-2.5 w-2.5 shrink-0 rounded-full', 'bg-tertiary' => $canVerify, 'bg-secondary' => $selected->isPaid(), 'bg-error' => $selected->status === OrderStatus::Rejected])></span>
                            <span class="truncate font-headline-sm text-headline-sm text-on-surface">#{{ $selected->invoice_number }}</span>
                        </div>
                        <x-badge :tone="$selected->status->tone()" :dot="false">{{ $selected->status->label() }}</x-badge>
                    </div>

                    <div class="flex flex-col gap-1 rounded-lg bg-surface-container-low p-space-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-caption text-caption text-on-surface-variant">Total Tagihan</span>
                            <span class="tabular font-label-md text-label-md font-bold text-primary">{{ rupiah($selected->amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-caption text-caption text-on-surface-variant">Metode</span>
                            <span class="font-caption text-caption text-on-surface">{{ $selected->payment_method->label() }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <span class="shrink-0 font-caption text-caption text-on-surface-variant">Tujuan Transfer</span>
                            <span class="text-right font-caption text-caption text-on-surface">{{ $accountLabel($selected->paymentAccount) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-caption text-caption text-on-surface-variant">Waktu Pesan</span>
                            <span class="font-caption text-caption text-on-surface">{{ $selected->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                        </div>
                    </div>

                    {{-- Bukti bayar --}}
                    <div class="flex flex-col gap-space-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-label-sm text-label-sm font-semibold text-on-surface">Bukti Pembayaran</span>
                            @if ($selected->payment_proof)
                                <a href="{{ route('admin.orders.proof', $selected) }}" target="_blank" class="flex items-center gap-0.5 font-caption text-caption text-primary hover:underline">
                                    Perbesar <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                </a>
                            @endif
                        </div>
                        <div class="flex min-h-[200px] items-center justify-center overflow-hidden rounded-xl bg-surface-container-highest">
                            @if ($proofIsImage)
                                <img src="{{ route('admin.orders.proof', $selected) }}" alt="Bukti pembayaran {{ $selected->invoice_number }}" class="max-h-[360px] w-full object-contain">
                            @elseif ($selected->payment_proof)
                                <a href="{{ route('admin.orders.proof', $selected) }}" target="_blank" class="flex flex-col items-center gap-1 text-primary">
                                    <span class="material-symbols-outlined text-[40px]">description</span>
                                    <span class="font-label-sm text-label-sm">Buka file bukti bayar</span>
                                </a>
                            @else
                                <div class="flex flex-col items-center gap-1 px-space-md text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[36px] text-outline">image_not_supported</span>
                                    <span class="font-caption text-caption">Customer belum mengunggah bukti pembayaran.</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Simulasi pembagian dana --}}
                    <div class="flex flex-col gap-space-xs rounded-xl bg-surface-container-low p-space-md">
                        <span class="font-label-sm text-label-sm font-bold text-on-surface">Pembagian Dana</span>
                        <div class="mt-1 flex flex-col gap-1.5 font-body-sm text-body-sm">
                            <div class="flex items-center justify-between text-on-surface">
                                <span>Dana masuk rekening:</span>
                                <span class="tabular font-semibold">{{ rupiah($selected->amount) }}</span>
                            </div>
                            @if ($selected->referralLink)
                                <div class="flex items-center justify-between gap-2 text-secondary">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">person_add</span>
                                        Komisi {{ $selected->referralLink->affiliate->name }} ({{ (float) $rate }}%):
                                    </span>
                                    <span class="tabular font-bold">+{{ rupiah($commissionAmount) }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between font-medium text-primary">
                                <span>Bagian product owner:</span>
                                <span class="tabular">+{{ rupiah($selected->amount - $commissionAmount) }}</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-1.5 font-caption text-caption text-on-surface-variant">
                            <span class="material-symbols-outlined text-[14px]">info</span>
                            <span>Akses produk muncul di akun {{ $selected->customer->email }}</span>
                        </div>
                    </div>

                    {{-- Aksi --}}
                    @if ($canVerify)
                        <div class="flex flex-col gap-2">
                            <form method="POST" action="{{ route('admin.orders.approve', $selected) }}">
                                @csrf
                                <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-space-md py-3 font-label-md text-label-md text-on-secondary shadow-md transition-all hover:bg-secondary/90 active:scale-[0.99]">
                                    <span class="material-symbols-outlined text-[20px]">mark_email_read</span>
                                    Verifikasi &amp; Buka Akses Produk
                                </button>
                            </form>

                            <button type="button" x-show="!rejecting" @click="rejecting = true"
                                    class="flex items-center justify-center gap-1 rounded-lg bg-error-container px-space-sm py-2 font-label-sm text-label-sm text-on-error-container transition-colors hover:bg-error/20">
                                <span class="material-symbols-outlined text-[16px]">cancel</span> Tolak Transaksi
                            </button>

                            <form method="POST" action="{{ route('admin.orders.reject', $selected) }}" x-show="rejecting" x-cloak class="flex flex-col gap-2 rounded-xl bg-error-container/40 p-space-sm">
                                @csrf
                                <label for="rejection_note" class="font-label-sm text-label-sm text-on-error-container">Alasan penolakan (terlihat oleh customer)</label>
                                <textarea id="rejection_note" name="rejection_note" rows="3" placeholder="Contoh: nominal transfer tidak sesuai tagihan"
                                          class="w-full rounded-lg border-0 bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-error">{{ old('rejection_note') }}</textarea>
                                <x-input-error :messages="$errors->get('rejection_note')" />
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="rejecting = false" class="rounded-lg bg-surface-container-high px-space-sm py-2 font-label-sm text-label-sm text-on-surface hover:bg-surface-container-highest">Batal</button>
                                    <button class="rounded-lg bg-error px-space-sm py-2 font-label-sm text-label-sm text-on-error hover:bg-error/90">Tolak Pembayaran</button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div @class([
                            'flex items-start gap-2 rounded-xl p-space-sm font-body-sm text-body-sm',
                            'bg-secondary-fixed/40 text-on-secondary-fixed-variant' => $selected->isPaid(),
                            'bg-error-container/50 text-on-error-container' => ! $selected->isPaid(),
                        ])>
                            <span class="material-symbols-outlined text-[18px]">{{ $selected->isPaid() ? 'verified' : 'block' }}</span>
                            <div>
                                {{ $selected->isPaid() ? 'Diverifikasi' : 'Ditolak' }} oleh {{ $selected->verifier?->name ?? '-' }}
                                @if ($selected->verified_at)
                                    · {{ $selected->verified_at->translatedFormat('d M Y, H:i') }} WIB
                                @endif
                                @if ($selected->rejection_note)
                                    <p class="mt-1 font-caption text-caption">Alasan: {{ $selected->rejection_note }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center gap-1 rounded-xl bg-surface-container-lowest p-space-xl text-center text-on-surface-variant shadow-sm">
                    <span class="material-symbols-outlined text-[36px] text-outline">fact_check</span>
                    <span class="font-label-md text-label-md text-on-surface">Pilih transaksi</span>
                    <span class="font-caption text-caption">Klik ikon mata pada tabel untuk melihat bukti bayar dan memverifikasi.</span>
                </div>
            @endif

            <div class="flex items-center gap-space-md rounded-xl bg-surface-container-low p-space-md">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-highest text-primary">
                    <span class="material-symbols-outlined text-[24px]">verified_user</span>
                </div>
                <div class="flex flex-col">
                    <span class="font-label-md text-label-md text-on-surface">Komisi otomatis</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">Saat pembayaran disetujui, komisi afiliator langsung dicatat sesuai persentase produk.</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
