@php
    $categoryIcon = match (true) {
        str_contains(strtolower((string) $product->category), 'course') => 'smart_display',
        str_contains(strtolower((string) $product->category), 'ebook'), str_contains(strtolower((string) $product->category), 'pdf') => 'menu_book',
        str_contains(strtolower((string) $product->category), 'template') => 'dashboard_customize',
        str_contains(strtolower((string) $product->category), 'software') => 'terminal',
        str_contains(strtolower((string) $product->category), 'asset'), str_contains(strtolower((string) $product->category), 'preset') => 'palette',
        default => 'inventory_2',
    };
@endphp

<x-layouts.shop :title="$product->name">
    {{-- Navigasi kembali --}}
    <div class="mb-space-md flex flex-wrap items-center justify-between gap-space-sm">
        <a href="{{ route('shop.products.index') }}"
           class="inline-flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant transition hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Kembali ke Katalog Produk</span>
        </a>

        @if ($product->category)
            <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-3 py-1 font-label-sm text-label-sm text-on-primary-fixed-variant">
                <span class="material-symbols-outlined text-[14px]">{{ $categoryIcon }}</span>
                {{ $product->category }}
            </span>
        @endif
    </div>

    {{-- Konten Utama: 1 kolom di HP (360px), 12 kolom di layar besar --}}
    <div class="grid grid-cols-1 items-start gap-space-lg lg:grid-cols-12">
        {{-- Kolom Kiri: Thumbnail & Deskripsi --}}
        <div class="flex flex-col gap-space-lg lg:col-span-7">
            {{-- Thumbnail Produk --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-surface-container-lowest shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)]">
                <div class="relative aspect-[16/10] w-full overflow-hidden bg-surface-container-low">
                    @if ($product->thumbnail)
                        <img
                            src="{{ asset('storage/'.$product->thumbnail) }}"
                            alt="{{ $product->name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="flex h-full w-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-surface-container-low to-surface-container p-space-lg text-primary">
                            <span class="material-symbols-outlined rounded-2xl bg-surface-container-lowest/95 p-5 text-[48px] text-primary shadow-sm">
                                {{ $categoryIcon }}
                            </span>
                            <span class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">
                                {{ $product->category ?? 'Produk Digital' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Deskripsi Produk --}}
            <section class="rounded-xl border border-slate-200 bg-surface-container-lowest p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] sm:p-space-lg">
                <div class="mb-space-sm flex items-center gap-2 border-b border-slate-100 pb-space-sm">
                    <span class="material-symbols-outlined text-[20px] text-primary">description</span>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Deskripsi Produk</h2>
                </div>

                @if ($product->description)
                    <div class="whitespace-pre-line font-body-lg text-body-lg leading-relaxed text-on-surface-variant">{{ $product->description }}</div>
                @else
                    <p class="font-body-md text-body-md italic text-outline">Belum ada deskripsi rinci untuk produk ini.</p>
                @endif
            </section>
        </div>

        {{-- Kolom Kanan: Panel Harga & Tombol Beli --}}
        <aside class="lg:sticky lg:top-24 lg:col-span-5">
            <div class="rounded-xl border border-slate-200 bg-surface-container-lowest p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] sm:p-space-lg">
                <div class="mb-space-sm flex flex-wrap items-center justify-between gap-2">
                    <span class="font-caption text-caption font-semibold uppercase tracking-wider text-primary">
                        {{ $product->category ?? 'Produk Digital' }}
                    </span>
                    <x-badge tone="success">Stok Lisensi Tersedia</x-badge>
                </div>

                <h1 class="font-headline-lg-mobile text-headline-lg-mobile tracking-tight text-on-surface md:font-headline-lg md:text-headline-lg">
                    {{ $product->name }}
                </h1>

                {{-- Harga --}}
                <div class="mt-space-md rounded-xl bg-surface-container-low p-space-md">
                    <span class="block font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                        Harga Produk
                    </span>
                    <div class="tabular mt-1 font-currency-display text-currency-display text-primary">
                        {{ rupiah($product->price) }}
                    </div>
                    <p class="mt-1 font-caption text-caption text-on-surface-variant">
                        Sekali bayar &middot; Akses unduh produk digital
                    </p>
                </div>

                {{-- Keunggulan / Info Pengiriman --}}
                <ul class="my-space-md space-y-2.5 border-y border-slate-100 py-space-md font-body-md text-body-md text-on-surface-variant">
                    <li class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[18px] text-secondary">check_circle</span>
                        <span>Unduh langsung dari halaman <strong>Pesanan Saya</strong> setelah pembayaran terverifikasi.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[18px] text-secondary">qr_code_scanner</span>
                        <span>Mendukung pembayaran via <strong>QRIS</strong> &amp; <strong>Transfer Bank</strong>.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined mt-0.5 shrink-0 text-[18px] text-secondary">verified_user</span>
                        <span>Tautan unduhan aman dengan proteksi <em>Signed URL</em>.</span>
                    </li>
                </ul>

                {{-- Tombol CTA Beli Sekarang: checkout hanya untuk akun pembeli (route role:customer) --}}
                @if (auth()->guest() || auth()->user()->isCustomer())
                    <a href="{{ route('shop.checkout', $product) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-container px-5 py-3 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                        <span>Beli sekarang</span>
                    </a>

                    @guest
                        <p class="mt-space-sm text-center font-caption text-caption text-on-surface-variant">
                            Belum masuk akun? Anda akan diarahkan ke halaman <strong>Masuk</strong> terlebih dahulu lalu kembali ke checkout.
                        </p>
                    @endguest
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                        <p class="flex items-center justify-center gap-2 font-label-md text-label-md text-on-surface">
                            <span class="material-symbols-outlined text-[20px] text-slate-400">lock</span>
                            <span>Pembelian hanya untuk akun pembeli</span>
                        </p>
                        <p class="mt-1.5 font-caption text-caption text-on-surface-variant">
                            Anda sedang masuk sebagai <strong>{{ auth()->user()->role->label() }}</strong>. Untuk membeli produk ini, keluar lalu masuk dengan akun pembeli.
                        </p>
                    </div>
                    <a href="{{ route('dashboard') }}"
                       class="mt-space-sm inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-5 py-3 font-label-md text-label-md text-on-surface transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                        <span class="material-symbols-outlined text-[20px]">dashboard</span>
                        <span>Kembali ke Dashboard</span>
                    </a>
                @endif
            </div>
        </aside>
    </div>

    <x-toast />
</x-layouts.shop>
