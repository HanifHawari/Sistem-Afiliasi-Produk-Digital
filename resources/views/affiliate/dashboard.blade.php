<x-layouts.affiliate title="Ringkasan Afiliasi">

    {{-- ================================================================
         HERO BANNER — sapaan + aksi navigasi cepat
         ================================================================ --}}
    <section class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 p-6 shadow-lg shadow-blue-700/20 sm:p-8">
        {{-- Orb dekoratif --}}
        <div class="pointer-events-none absolute -right-10 -top-10 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-12 right-1/3 h-40 w-40 rounded-full bg-indigo-400/20 blur-2xl"></div>

        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            {{-- Teks sapaan --}}
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 backdrop-blur-sm">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                    <span class="text-xs font-semibold text-white/90">Dashboard Afiliasi Anda</span>
                </div>
                <h1 class="text-2xl font-bold leading-snug tracking-tight text-white sm:text-3xl">
                    Halo, {{ auth()->user()->name }} 👋
                </h1>
                <p class="max-w-xl text-sm text-blue-100">
                    Pantau performa link afiliasi, penjualan, dan komisi Anda secara langsung.
                    Teruskan semangat berpromosi!
                </p>
            </div>

            {{-- Aksi cepat — hanya ke route yang terdaftar --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('affiliate.withdrawals.index') }}"
                   class="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    Tarik Komisi
                </a>
                <a href="{{ route('affiliate.links.index') }}"
                   class="flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/25 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">add_link</span>
                    Link & Kampanye
                </a>
                <a href="{{ route('affiliate.promo-materials.index') }}"
                   class="flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">perm_media</span>
                    Materi Promosi
                </a>
            </div>
        </div>
    </section>

    {{-- ================================================================
         KARTU STATISTIK — 4 KPI: Klik, Penjualan, Saldo, Total Komisi
         Menggunakan markup Tailwind lokal karena x-stat-card bertema biru
         polos dan tidak sesuai desain Stitch yang membutuhkan variasi warna.
         ================================================================ --}}
    <section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- KPI 1: Total Klik Link --}}
        <div class="flex flex-col justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Klik Link</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {{ number_format($totalClicks, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-[22px]">ads_click</span>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">Semua klik dari seluruh link referral Anda</p>
        </div>

        {{-- KPI 2: Penjualan Berhasil --}}
        <div class="flex flex-col justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Penjualan Berhasil</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        {{ number_format($totalSales, 0, ',', '.') }}
                        <span class="text-base font-normal text-slate-400">order</span>
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <span class="material-symbols-outlined text-[22px]">shopping_bag</span>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">Order dari referral yang sudah terverifikasi (Paid)</p>
        </div>

        {{-- KPI 3: Saldo Siap Cair --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-emerald-100"></div>
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo Siap Cair</p>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Siap Ditarik</span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                        Rp {{ number_format($balance, 0, ',', '.') }}
                    </p>
                </div>
                <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <span class="material-symbols-outlined text-[22px]" style="font-variation-settings: 'FILL' 1">account_balance_wallet</span>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-slate-400">Komisi Approved dikurangi penarikan</p>
                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
            </div>
        </div>

        {{-- KPI 4: Total Komisi Diterima --}}
        <div class="flex flex-col justify-between rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Komisi Diterima</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                        Rp {{ number_format($totalCommission, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <span class="material-symbols-outlined text-[22px]">paid</span>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-400">Akumulasi komisi berstatus Approved</p>
        </div>

    </section>

    {{-- ================================================================
         QUICK ACTIONS — navigasi cepat ke fitur utama
         ================================================================ --}}
    <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="{{ route('affiliate.products.index') }}"
           class="flex flex-col items-center gap-2 rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-100 transition hover:shadow-md hover:ring-blue-200 active:scale-95">
            <span class="material-symbols-outlined text-[28px] text-blue-600">storefront</span>
            <span class="text-xs font-semibold text-slate-700">Katalog Produk</span>
        </a>
        <a href="{{ route('affiliate.links.index') }}"
           class="flex flex-col items-center gap-2 rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-100 transition hover:shadow-md hover:ring-blue-200 active:scale-95">
            <span class="material-symbols-outlined text-[28px] text-indigo-600">link</span>
            <span class="text-xs font-semibold text-slate-700">Link & Kampanye</span>
        </a>
        <a href="{{ route('affiliate.promo-materials.index') }}"
           class="flex flex-col items-center gap-2 rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-100 transition hover:shadow-md hover:ring-blue-200 active:scale-95">
            <span class="material-symbols-outlined text-[28px] text-violet-600">perm_media</span>
            <span class="text-xs font-semibold text-slate-700">Materi Promosi</span>
        </a>
        <a href="{{ route('affiliate.withdrawals.index') }}"
           class="flex flex-col items-center gap-2 rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-slate-100 transition hover:shadow-md hover:ring-emerald-200 active:scale-95">
            <span class="material-symbols-outlined text-[28px] text-emerald-600">account_balance_wallet</span>
            <span class="text-xs font-semibold text-slate-700">Penarikan Komisi</span>
        </a>
    </section>

    {{-- ================================================================
         RIWAYAT PENJUALAN & KOMISI
         ================================================================ --}}
    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">

        {{-- Header tabel --}}
        <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <span class="material-symbols-outlined text-[22px] text-emerald-500">verified</span>
                    Riwayat Penjualan & Komisi Masuk
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">5 transaksi referral terbaru yang sudah berhasil.</p>
            </div>
            <a href="{{ route('affiliate.withdrawals.index') }}"
               class="btn-outline shrink-0 text-sm">
                Lihat Semua Penarikan
            </a>
        </div>

        @if ($recentSales->isEmpty())
            {{-- ============================================================
                 EMPTY STATE — ditampilkan jika belum ada penjualan
                 ============================================================ --}}
            <div class="flex flex-col items-center gap-4 px-6 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <span class="material-symbols-outlined text-[36px]">receipt_long</span>
                </div>
                <div>
                    <p class="text-base font-semibold text-slate-700">Belum Ada Transaksi</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-400">
                        Bagikan link afiliasi Anda dan mulai promosikan produk digital agar komisi pertama Anda segera masuk.
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('affiliate.links.index') }}" class="btn-primary text-sm">
                        <span class="material-symbols-outlined text-[16px]">add_link</span>
                        Buat Link Afiliasi
                    </a>
                    <a href="{{ route('affiliate.products.index') }}" class="btn-outline text-sm">
                        Lihat Produk
                    </a>
                </div>
            </div>

        @else
            {{-- ============================================================
                 TABEL TRANSAKSI — responsive, overflow-x-auto
                 ============================================================ --}}
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">No. Invoice</th>
                            <th class="px-5 py-3">Pembeli</th>
                            <th class="px-5 py-3">Produk</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3 text-right">Nominal Transaksi</th>
                            <th class="px-5 py-3 text-right">Komisi</th>
                            <th class="px-5 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm text-slate-700">
                        @foreach ($recentSales as $order)
                            <tr class="transition hover:bg-slate-50/60">
                                {{-- Nomor Invoice --}}
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-sm font-semibold text-blue-600">
                                    #{{ $order->invoice_number ?? $order->id }}
                                </td>

                                {{-- Nama Pembeli --}}
                                <td class="px-5 py-3.5 font-medium text-slate-800">
                                    {{ $order->customer?->name ?? '—' }}
                                </td>

                                {{-- Nama Produk --}}
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                                        <span class="line-clamp-1 max-w-[200px]">{{ $order->product?->name ?? '—' }}</span>
                                    </div>
                                </td>

                                {{-- Tanggal Transaksi --}}
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-400">
                                    {{ $order->created_at->translatedFormat('d M Y, H:i') }} WIB
                                </td>

                                {{-- Nominal Transaksi --}}
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-800">
                                    Rp {{ number_format($order->amount, 0, ',', '.') }}
                                </td>

                                {{-- Nominal Komisi --}}
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-emerald-600">
                                    @if ($order->commission)
                                        +Rp {{ number_format($order->commission->amount, 0, ',', '.') }}
                                    @else
                                        <span class="font-normal text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Status Transaksi --}}
                                <td class="px-5 py-3.5 text-center">
                                    <x-badge :tone="$order->status->tone()">
                                        {{ $order->status->label() }}
                                    </x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer tabel --}}
            <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-400">
                Menampilkan {{ $recentSales->count() }} transaksi terakhir yang sudah Paid.
            </div>
        @endif

    </section>

</x-layouts.affiliate>
