@php
    $categoryIcon = fn (?string $category) => match (true) {
        str_contains(strtolower((string) $category), 'course') => 'smart_display',
        str_contains(strtolower((string) $category), 'ebook'), str_contains(strtolower((string) $category), 'pdf') => 'menu_book',
        str_contains(strtolower((string) $category), 'template') => 'dashboard_customize',
        str_contains(strtolower((string) $category), 'software') => 'terminal',
        str_contains(strtolower((string) $category), 'asset'), str_contains(strtolower((string) $category), 'preset') => 'palette',
        default => 'inventory_2',
    };
@endphp

<x-layouts.affiliate title="Katalog Produk">

    {{-- ================================================================
         PAGE HEADER
         ================================================================ --}}
    <x-page-header
        eyebrow="Ruang Afiliator"
        title="Katalog Produk"
        description="Pilih produk digital aktif untuk dipromosikan dan dapatkan komisi dari setiap penjualan."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3.5 py-1.5 font-mono text-xs font-semibold text-blue-700 ring-1 ring-blue-200">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span>{{ $products->total() }} Produk Aktif</span>
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-6" data-enter="up">

        {{-- ================================================================
             TOOLBAR: KATEGORI & PENCARIAN
             ================================================================ --}}
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            {{-- Filter Kategori --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('affiliate.products.index', array_filter(['q' => $search])) }}"
                   @class([
                       'shrink-0 rounded-xl px-3.5 py-2 text-xs font-semibold transition',
                       'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $selectedCategory === '',
                       'bg-slate-100 text-slate-600 hover:bg-slate-200' => $selectedCategory !== '',
                   ])>
                    Semua Kategori
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('affiliate.products.index', array_filter(['category' => $category, 'q' => $search])) }}"
                       @class([
                           'shrink-0 rounded-xl px-3.5 py-2 text-xs font-semibold transition',
                           'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => $selectedCategory === $category,
                           'bg-slate-100 text-slate-600 hover:bg-slate-200' => $selectedCategory !== $category,
                       ])>
                        {{ $category }}
                    </a>
                @endforeach
            </div>

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('affiliate.products.index') }}" class="flex w-full items-center gap-2 sm:w-80">
                @if ($selectedCategory !== '')
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </span>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari produk digital..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                </div>
                <button type="submit" class="btn-primary btn-sm shrink-0">
                    Cari
                </button>
                @if ($search !== '' || $selectedCategory !== '')
                    <a href="{{ route('affiliate.products.index') }}" class="btn-outline btn-sm shrink-0 text-slate-500 hover:text-slate-700" title="Reset filter">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </a>
                @endif
            </form>
        </div>

        {{-- ================================================================
             GRID PRODUK
             ================================================================ --}}
        @if ($products->isNotEmpty())
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @php
                        $link = $userLinks->get($product->id);
                        $commissionAmount = $product->commissionAmount();
                    @endphp

                    <div class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm transition hover:shadow-md hover:ring-1 hover:ring-blue-200">
                        {{-- Atas: Thumbnail & Info Utama --}}
                        <div>
                            {{-- Area Thumbnail --}}
                            <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
                                @if ($product->thumbnail)
                                    <img
                                        src="{{ asset('storage/' . $product->thumbnail) }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br from-blue-50 via-slate-50 to-indigo-50 text-blue-600">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                                            <span class="material-symbols-outlined text-[32px]">
                                                {{ $categoryIcon($product->category) }}
                                            </span>
                                        </div>
                                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                            {{ $product->category ?? 'Produk Digital' }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Badge Kategori --}}
                                @if ($product->category)
                                    <div class="absolute left-3 top-3">
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-sm backdrop-blur-sm">
                                            <span class="material-symbols-outlined text-[13px] text-blue-600">{{ $categoryIcon($product->category) }}</span>
                                            {{ $product->category }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Badge Rate Komisi --}}
                                <div class="absolute right-3 top-3">
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm backdrop-blur-sm">
                                        <span class="material-symbols-outlined text-[13px]">percent</span>
                                        Komisi {{ (float) $product->commission_rate }}%
                                    </span>
                                </div>
                            </div>

                            {{-- Info Produk --}}
                            <div class="p-5">
                                <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600">
                                    {{ $product->name }}
                                </h3>

                                @if ($product->description)
                                    <p class="mt-1.5 line-clamp-2 text-xs text-slate-500 leading-relaxed">
                                        {{ $product->description }}
                                    </p>
                                @else
                                    <p class="mt-1.5 text-xs italic text-slate-400">
                                        Tidak ada deskripsi tambahan.
                                    </p>
                                @endif

                                {{-- Rincian Harga & Komisi --}}
                                <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                    <div>
                                        <span class="block text-[11px] font-medium uppercase tracking-wide text-slate-400">Harga Jual</span>
                                        <span class="tabular font-mono text-sm font-bold text-slate-800">
                                            {{ rupiah($product->price) }}
                                        </span>
                                    </div>
                                    <div class="border-l border-slate-200/70 pl-3">
                                        <span class="block text-[11px] font-medium uppercase tracking-wide text-emerald-600">Komisi / Sale</span>
                                        <span class="tabular font-mono text-sm font-bold text-emerald-600">
                                            {{ rupiah($commissionAmount) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Materi Promosi Info --}}
                                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px] text-slate-400">perm_media</span>
                                        <span>{{ $product->promo_materials_count }} materi promosi</span>
                                    </span>
                                    <a href="{{ route('affiliate.promo-materials.index') }}" class="font-medium text-blue-600 hover:text-blue-700">
                                        Lihat Materi &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Bawah: Aksi Referral Link --}}
                        <div class="border-t border-slate-100 bg-white p-4">
                            @if ($link)
                                {{-- KONDISI 1: SUDAH MEMILIKI REFERRAL LINK --}}
                                <div x-data="{
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
                                }" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Link Siap Dipromosikan
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">Kode: {{ $link->code }}</span>
                                    </div>

                                    <div class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 p-1">
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $link->url() }}"
                                            class="w-full bg-transparent px-2.5 text-xs font-mono text-slate-600 focus:outline-none select-all"
                                            title="Klik untuk memilih seluruh link"
                                        >
                                        <button
                                            type="button"
                                            @click="copy()"
                                            :class="copied ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white hover:bg-blue-700'"
                                            class="inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-sm transition active:scale-95"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- KONDISI 2: BELUM MEMILIKI REFERRAL LINK --}}
                                <form method="POST" action="{{ route('affiliate.products.generate-link', $product) }}" x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button
                                        type="submit"
                                        :disabled="loading"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span class="material-symbols-outlined text-[18px]" x-show="!loading">add_link</span>
                                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" x-show="loading" x-cloak></span>
                                        <span x-text="loading ? 'Membuat Link...' : 'Dapatkan Link Referral'">Dapatkan Link Referral</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ================================================================
                 PAGINASI
                 ================================================================ --}}
            @if ($products->hasPages())
                <div class="mt-2 rounded-2xl border border-slate-200/80 bg-white">
                    <x-pager :paginator="$products" noun="produk" />
                </div>
            @endif

        @else
            {{-- ================================================================
                 EMPTY STATE
                 ================================================================ --}}
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-[36px]">inventory_2</span>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-800">
                    {{ $search !== '' || $selectedCategory !== '' ? 'Produk Tidak Ditemukan' : 'Belum Ada Produk Aktif' }}
                </h3>
                <p class="mt-1 max-w-md text-xs text-slate-500">
                    @if ($search !== '' || $selectedCategory !== '')
                        Tidak ada produk yang cocok dengan kriteria pencarian Anda. Coba kata kunci lain atau bersihkan filter.
                    @else
                        Saat ini belum ada produk digital aktif yang tersedia untuk dipromosikan. Silakan periksa kembali nanti.
                    @endif
                </p>

                @if ($search !== '' || $selectedCategory !== '')
                    <div class="mt-5">
                        <a href="{{ route('affiliate.products.index') }}" class="btn-outline btn-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                            Reset Pencarian &amp; Filter
                        </a>
                    </div>
                @endif
            </div>
        @endif

    </div>

</x-layouts.affiliate>
