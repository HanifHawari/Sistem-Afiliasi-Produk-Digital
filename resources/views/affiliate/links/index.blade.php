<x-layouts.affiliate title="Link & Kampanye">

    {{-- ================================================================
         1. PAGE HEADER
         ================================================================ --}}
    <x-page-header
        eyebrow="Ruang Afiliator"
        title="Link & Kampanye"
        description="Kelola link referral dan pantau performa setiap kampanye produk digital Anda."
    >
        <x-slot:actions>
            <x-badge tone="info" class="font-mono text-xs">
                <span class="material-symbols-outlined text-[15px]">link</span>
                <span>{{ $summary['total_links'] }} Link</span>
            </x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-6" data-enter="up">

        {{-- ================================================================
             2. KPI CARDS (Ringkasan Keseluruhan Seluruh Kampanye)
             ================================================================ --}}
        <section>
            <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Ringkasan Performa Afiliasi</h2>
                    <p class="text-xs text-slate-400">Statistik keseluruhan seluruh kampanye Anda (tidak terpengaruh filter tabel).</p>
                </div>
            </div>

            <x-stat-grid>
                {{-- KPI 1: Total Link --}}
                <x-stat-card
                    label="Total Link"
                    :value="number_format($summary['total_links'], 0, ',', '.')"
                    caption="Referral link aktif yang Anda miliki"
                >
                    <span class="material-symbols-outlined text-[18px]">link</span>
                    <span>Semua kampanye</span>
                </x-stat-card>

                {{-- KPI 2: Total Klik --}}
                <x-stat-card
                    label="Total Klik"
                    :value="number_format($summary['clicks'], 0, ',', '.')"
                    caption="Semua klik dari seluruh link referral Anda"
                >
                    <span class="material-symbols-outlined text-[18px]">ads_click</span>
                    <span>Klik masuk</span>
                </x-stat-card>

                {{-- KPI 3: Penjualan Berhasil --}}
                <x-stat-card
                    label="Penjualan Berhasil"
                    :value="number_format($summary['paid_orders'], 0, ',', '.') . ' order'"
                    caption="Order referral terverifikasi (Paid)"
                >
                    <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                    <span>Order Paid</span>
                </x-stat-card>

                {{-- KPI 4: Conversion Rate --}}
                <x-stat-card
                    label="Conversion Rate"
                    :value="number_format($summary['conversion_rate'], 2, ',', '.') . '%'"
                    caption="Rasio penjualan per klik (Paid / Klik)"
                >
                    <span class="material-symbols-outlined text-[18px]">trending_up</span>
                    <span>Konversi keseluruhan</span>
                </x-stat-card>
            </x-stat-grid>
        </section>

        {{-- ================================================================
             3. QUICK LINK GENERATOR
             ================================================================ --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600">
                        <span class="material-symbols-outlined text-[16px]">bolt</span>
                        Quick Link Generator
                    </div>
                    <h3 class="card-title mt-1">Buat Link Referral Cepat</h3>
                    <p class="card-subtitle">
                        Pilih produk digital aktif yang belum Anda promosikan untuk langsung menghasilkan link referral unik Anda.
                    </p>
                </div>
            </div>

            <div class="card-body">
                @if ($productsWithoutLink->isNotEmpty())
                    <form
                        method="POST"
                        x-data="{
                            selectedSlug: '{{ $productsWithoutLink->first()->slug }}',
                            loading: false,
                            baseUrl: '{{ route('affiliate.products.generate-link', '__SLUG__') }}',
                            submitForm(e) {
                                if (!this.selectedSlug) {
                                    e.preventDefault();
                                    return;
                                }
                                this.loading = true;
                                this.$el.action = this.baseUrl.replace('__SLUG__', encodeURIComponent(this.selectedSlug));
                            }
                        }"
                        :action="baseUrl.replace('__SLUG__', encodeURIComponent(selectedSlug))"
                        @submit="submitForm($event)"
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        @csrf
                        <div class="relative min-w-0 flex-1">
                            <select
                                name="product_slug"
                                x-model="selectedSlug"
                                class="input"
                                required
                            >
                                @foreach ($productsWithoutLink as $product)
                                    <option value="{{ $product->slug }}">
                                        {{ $product->name }} — Komisi {{ $product->commission_rate }}% (Rp {{ number_format($product->commissionAmount(), 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button
                            type="submit"
                            :disabled="loading || !selectedSlug"
                            class="btn-primary btn-sm shrink-0"
                        >
                            <span class="material-symbols-outlined text-[18px]" x-show="!loading">add_link</span>
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" x-show="loading" x-cloak></span>
                            <span x-text="loading ? 'Membuat Link...' : 'Generate Link'">Generate Link</span>
                        </button>
                    </form>
                @else
                    <div class="flex flex-col items-start justify-between gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-4 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Semua Produk Sudah Memiliki Link</p>
                                <p class="text-xs text-slate-500">Anda telah membuat referral link untuk seluruh produk digital aktif yang tersedia saat ini.</p>
                            </div>
                        </div>
                        <a href="{{ route('affiliate.products.index') }}" class="btn-outline btn-sm shrink-0">
                            <span class="material-symbols-outlined text-[16px]">storefront</span>
                            Buka Katalog Produk
                        </a>
                    </div>
                @endif
            </div>
        </section>

        {{-- ================================================================
             4. TOOLBAR: PENCARIAN & FILTER PRODUK
             ================================================================ --}}
        <form method="GET" action="{{ route('affiliate.links.index') }}" class="toolbar">
            {{-- Input Pencarian: Nama Produk atau Kode Referral --}}
            <div class="search">
                <span class="material-symbols-outlined">search</span>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari nama produk atau kode referral..."
                >
            </div>

            {{-- Filter Dropdown Berdasarkan Produk --}}
            <select
                name="product_id"
                onchange="this.form.submit()"
                class="input w-full sm:w-56"
            >
                <option value="">Semua Produk</option>
                @foreach ($userProducts as $productOption)
                    <option value="{{ $productOption->id }}" @selected((string) $selectedProduct === (string) $productOption->id)>
                        {{ $productOption->name }}
                    </option>
                @endforeach
            </select>

            <div class="flex items-center gap-2">
                {{-- Tombol Cari --}}
                <button type="submit" class="btn-primary btn-sm shrink-0">
                    Cari
                </button>

                {{-- Tombol Reset Filter --}}
                @if ($search !== '' || ! empty($selectedProduct))
                    <a href="{{ route('affiliate.links.index') }}" class="btn-outline btn-sm shrink-0 text-slate-500 hover:text-slate-700" title="Reset pencarian & filter">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                        <span class="hidden sm:inline">Reset</span>
                    </a>
                @endif
            </div>
        </form>

        {{-- ================================================================
             5. DAFTAR LINK & KAMPANYE (DESKTOP & MOBILE)
             ================================================================ --}}
        @if ($links->isNotEmpty())
            {{-- A. TAMPILAN DESKTOP (TABLE) --}}
            <section class="card hidden overflow-hidden lg:block">
                <div class="relative overflow-x-auto">
                    <table class="data-table ledger">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kode Referral</th>
                                <th>URL Referral</th>
                                <th class="text-center">Jumlah Klik</th>
                                <th class="text-center">Penjualan Berhasil</th>
                                <th class="text-center">Conversion Rate</th>
                                <th>Tanggal Dibuat</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($links as $link)
                                <tr x-data="{
                                    copied: false,
                                    copy() {
                                        const text = '{{ $link->url() }}';
                                        if (navigator.clipboard && window.isSecureContext) {
                                            navigator.clipboard.writeText(text).then(() => this.triggerSuccess());
                                        } else {
                                            const textarea = document.createElement('textarea');
                                            textarea.value = text;
                                            document.body.appendChild(textarea);
                                            textarea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textarea);
                                            this.triggerSuccess();
                                        }
                                    },
                                    triggerSuccess() {
                                        this.copied = true;
                                        setTimeout(() => this.copied = false, 2000);
                                    }
                                }">
                                    {{-- Kolom 1: Produk (thumbnail, nama, harga) --}}
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if ($link->product?->thumbnail)
                                                <img
                                                    src="{{ asset('storage/' . $link->product->thumbnail) }}"
                                                    alt="{{ $link->product->name }}"
                                                    class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 ring-slate-100"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0 max-w-[220px]">
                                                <p class="truncate font-semibold text-slate-900" title="{{ $link->product?->name }}">
                                                    {{ $link->product?->name ?? 'Produk Dihapus' }}
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    Rp {{ number_format($link->product?->price ?? 0, 0, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Kolom 2: Kode Referral --}}
                                    <td class="whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-semibold text-slate-800 ring-1 ring-slate-200/70">
                                            {{ $link->code }}
                                        </span>
                                    </td>

                                    {{-- Kolom 3: URL Referral --}}
                                    <td>
                                        <div class="flex max-w-[200px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1">
                                            <span class="truncate font-mono text-xs text-slate-600 select-all" title="{{ $link->url() }}">
                                                {{ $link->url() }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Kolom 4: Jumlah Klik --}}
                                    <td class="whitespace-nowrap text-center">
                                        <div class="inline-flex items-center gap-1 font-semibold text-slate-900">
                                            <span class="material-symbols-outlined text-[16px] text-slate-400">ads_click</span>
                                            <span>{{ number_format($link->clicks_count, 0, ',', '.') }}</span>
                                        </div>
                                    </td>

                                    {{-- Kolom 5: Penjualan Berhasil --}}
                                    <td class="whitespace-nowrap text-center">
                                        <div class="inline-flex items-center gap-1 font-semibold text-slate-900">
                                            <span class="material-symbols-outlined text-[16px] text-slate-400">shopping_bag</span>
                                            <span>{{ number_format($link->paid_orders_count, 0, ',', '.') }}</span>
                                        </div>
                                    </td>

                                    {{-- Kolom 6: Conversion Rate --}}
                                    <td class="whitespace-nowrap text-center">
                                        <x-badge :tone="$link->conversion_rate > 0 ? 'success' : 'neutral'" class="font-mono">
                                            {{ number_format($link->conversion_rate, 2, ',', '.') }}%
                                        </x-badge>
                                    </td>

                                    {{-- Kolom 7: Tanggal Dibuat --}}
                                    <td class="whitespace-nowrap text-xs text-slate-500">
                                        {{ $link->created_at?->translatedFormat('d M Y') ?? '—' }}
                                    </td>

                                    {{-- Kolom 8: Aksi Salin Link --}}
                                    <td class="whitespace-nowrap text-right">
                                        <button
                                            type="button"
                                            @click="copy()"
                                            :class="copied ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm transition active:scale-95"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pager desktop --}}
                @if ($links->hasPages())
                    <x-pager :paginator="$links" noun="link kampanye" />
                @endif
            </section>

            {{-- B. TAMPILAN MOBILE (KARTU RESPONSIVE) --}}
            <section class="flex flex-col gap-4 lg:hidden">
                @foreach ($links as $link)
                    <div class="card p-4" x-data="{
                        copied: false,
                        copy() {
                            const text = '{{ $link->url() }}';
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(text).then(() => this.triggerSuccess());
                            } else {
                                const textarea = document.createElement('textarea');
                                textarea.value = text;
                                document.body.appendChild(textarea);
                                textarea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textarea);
                                this.triggerSuccess();
                            }
                        },
                        triggerSuccess() {
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        }
                    }">
                        {{-- Header kartu: Produk & Kode --}}
                        <div class="flex items-start gap-3">
                            @if ($link->product?->thumbnail)
                                <img
                                    src="{{ asset('storage/' . $link->product->thumbnail) }}"
                                    alt="{{ $link->product->name }}"
                                    class="h-12 w-12 shrink-0 rounded-xl object-cover ring-1 ring-slate-100"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                    <span class="material-symbols-outlined text-[24px]">inventory_2</span>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200/70">
                                        {{ $link->code }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $link->created_at?->translatedFormat('d M Y') ?? '—' }}
                                    </span>
                                </div>
                                <h4 class="mt-1 line-clamp-1 font-semibold text-slate-900" title="{{ $link->product?->name }}">
                                    {{ $link->product?->name ?? 'Produk Dihapus' }}
                                </h4>
                                <p class="text-xs text-slate-500">
                                    Rp {{ number_format($link->product?->price ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>

                        {{-- 3 Metrik: Klik, Penjualan, Conversion Rate --}}
                        <div class="mt-3 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-2.5 text-center">
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Klik</span>
                                <p class="mt-0.5 font-bold text-slate-900">
                                    {{ number_format($link->clicks_count, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Penjualan</span>
                                <p class="mt-0.5 font-bold text-slate-900">
                                    {{ number_format($link->paid_orders_count, 0, ',', '.') }}
                                </p>
                            </div>
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Konversi</span>
                                <div class="mt-0.5">
                                    <x-badge :tone="$link->conversion_rate > 0 ? 'success' : 'neutral'" class="font-mono text-[10px]">
                                        {{ number_format($link->conversion_rate, 2, ',', '.') }}%
                                    </x-badge>
                                </div>
                            </div>
                        </div>

                        {{-- URL & Tombol Salin --}}
                        <div class="mt-3 flex items-center gap-2">
                            <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="truncate font-mono text-xs text-slate-600 select-all" title="{{ $link->url() }}">
                                    {{ $link->url() }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="copy()"
                                :class="copied ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                class="inline-flex shrink-0 items-center gap-1 rounded-xl px-3.5 py-2 text-xs font-semibold shadow-sm transition active:scale-95"
                            >
                                <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- Pager mobile --}}
                @if ($links->hasPages())
                    <div class="card">
                        <x-pager :paginator="$links" noun="link kampanye" />
                    </div>
                @endif
            </section>

        @else
            {{-- ================================================================
                 6. EMPTY STATES
                 ================================================================ --}}
            @if ($summary['total_links'] === 0)
                {{-- Kondisi 1: Afiliator belum memiliki referral link sama sekali --}}
                <section class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <span class="material-symbols-outlined text-[36px]">add_link</span>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">
                        Belum Memiliki Referral Link
                    </h3>
                    <p class="mt-1 max-w-md text-xs text-slate-500">
                        Anda belum memiliki link referral untuk produk digital. Gunakan generator di atas atau pilih produk dari katalog untuk mulai mempromosikan.
                    </p>
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('affiliate.products.index') }}" class="btn-primary btn-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">storefront</span>
                            Buka Katalog Produk
                        </a>
                    </div>
                </section>
            @else
                {{-- Kondisi 2: Tidak ada hasil pencarian atau filter --}}
                <section class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <span class="material-symbols-outlined text-[36px]">search_off</span>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">
                        Link Tidak Ditemukan
                    </h3>
                    <p class="mt-1 max-w-md text-xs text-slate-500">
                        Tidak ada link referral yang cocok dengan pencarian {{ $search !== '' ? "\"{$search}\"" : '' }} atau filter produk yang dipilih.
                    </p>
                    <div class="mt-5">
                        <a href="{{ route('affiliate.links.index') }}" class="btn-outline btn-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                            Reset Pencarian &amp; Filter
                        </a>
                    </div>
                </section>
            @endif
        @endif

    </div>

</x-layouts.affiliate>
