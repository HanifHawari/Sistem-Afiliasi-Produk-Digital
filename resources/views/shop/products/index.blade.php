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

<x-layouts.shop title="Katalog Produk Digital">
    <x-page-header
        eyebrow="Katalog Toko"
        title="Produk Digital"
        description="Temukan e-course, template, ebook, dan aset digital siap pakai dengan akses instan."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-3.5 py-1.5 font-label-sm text-label-sm text-on-primary-fixed-variant">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span class="tabular">{{ $products->total() }} produk tersedia</span>
            </span>
        </x-slot:actions>
    </x-page-header>

    {{-- Filter & Pencarian --}}
    <div class="mb-space-lg flex flex-col gap-space-sm rounded-xl border border-slate-200 bg-surface-container-lowest p-space-md shadow-[0_1px_3px_rgba(30,27,75,0.04)] sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <a href="{{ route('shop.products.index', array_filter(['q' => $search])) }}"
               @class([
                   ' shrink-0 rounded-full px-3 py-1.5 font-label-sm text-label-sm transition',
                   'bg-primary-container text-on-primary shadow-sm' => $selectedCategory === '',
                   'bg-surface-container-low text-on-surface-variant hover:bg-surface-container' => $selectedCategory !== '',
               ])>
                Semua Kategori
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('shop.products.index', array_filter(['category' => $category, 'q' => $search])) }}"
                   @class([
                       'shrink-0 rounded-full px-3 py-1.5 font-label-sm text-label-sm transition',
                       'bg-primary-container text-on-primary shadow-sm' => $selectedCategory === $category,
                       'bg-surface-container-low text-on-surface-variant hover:bg-surface-container' => $selectedCategory !== $category,
                   ])>
                    {{ $category }}
                </a>
            @endforeach
        </div>

        <form method="GET"
              action="{{ route('shop.products.index') }}"
              x-data="{ submitting: false }"
              @submit="submitting = true"
              class="flex w-full items-center gap-2 sm:w-80">
            @if ($selectedCategory !== '')
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <div class="relative min-w-0 flex-1">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline">search</span>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari produk digital..."
                    class="w-full rounded-lg border border-slate-300 bg-surface-container-lowest py-2 pl-9 pr-3 font-body-md text-body-md text-on-surface placeholder:text-slate-400 focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>
            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-primary-container px-3.5 py-2 font-label-sm text-label-sm text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-text="submitting ? '...' : 'Cari'">Cari</span>
            </button>
        </form>
    </div>

    {{-- Grid Produk: 1 kolom di HP (360px), 2 kolom di tablet, 3 kolom di layar besar --}}
    @if ($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 lg:grid-cols-3 lg:gap-space-lg">
            @foreach ($products as $product)
                <article class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-surface-container-lowest shadow-[0_1px_3px_rgba(30,27,75,0.04),0_1px_2px_rgba(30,27,75,0.02)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(30,27,75,0.08)]">
                    {{-- Thumbnail --}}
                    <a href="{{ route('shop.products.show', $product) }}" class="relative block aspect-[16/10] w-full overflow-hidden bg-surface-container-low">
                        @if ($product->thumbnail)
                            <img
                                src="{{ asset('storage/'.$product->thumbnail) }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br from-surface-container-low to-surface-container p-space-md text-primary">
                                <span class="material-symbols-outlined rounded-2xl bg-surface-container-lowest/90 p-3.5 text-[36px] text-primary shadow-sm">
                                    {{ $categoryIcon($product->category) }}
                                </span>
                                <span class="font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                                    {{ $product->category ?? 'Produk Digital' }}
                                </span>
                            </div>
                        @endif

                        @if ($product->category)
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-surface-container-lowest/95 px-2.5 py-1 font-label-sm text-label-sm text-on-primary-fixed-variant shadow-sm backdrop-blur-sm">
                                <span class="material-symbols-outlined text-[14px] text-primary">{{ $categoryIcon($product->category) }}</span>
                                {{ $product->category }}
                            </span>
                        @endif
                    </a>

                    {{-- Isi Kartu --}}
                    <div class="flex flex-1 flex-col justify-between p-space-md sm:p-space-lg">
                        <div>
                            <h2 class="font-headline-sm text-headline-sm text-on-surface transition-colors group-hover:text-primary">
                                <a href="{{ route('shop.products.show', $product) }}" class="line-clamp-2">
                                    {{ $product->name }}
                                </a>
                            </h2>

                            @if ($product->description)
                                <p class="mt-1.5 line-clamp-2 font-body-md text-body-md text-on-surface-variant">
                                    {{ $product->description }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-space-md border-t border-slate-100 pt-space-md">
                            <div class="flex flex-col gap-space-sm sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <span class="block font-caption text-caption uppercase tracking-wider text-on-surface-variant">Harga</span>
                                    <span class="tabular font-currency-metric text-currency-metric text-on-surface">
                                        {{ rupiah($product->price) }}
                                    </span>
                                </div>

                                <a href="{{ route('shop.products.show', $product) }}"
                                   class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 active:scale-[0.98] sm:w-auto">
                                    <span>Lihat detail</span>
                                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Paginasi (12 per halaman) --}}
        @if ($products->hasPages())
            <div class="mt-space-xl flex flex-col items-center justify-between gap-space-sm rounded-xl border border-slate-200 bg-surface-container-lowest px-space-md py-3 sm:flex-row">
                <p class="font-caption text-caption text-on-surface-variant">
                    Menampilkan <span class="tabular font-semibold text-on-surface">{{ $products->firstItem() }}–{{ $products->lastItem() }}</span>
                    dari <span class="tabular font-semibold text-on-surface">{{ $products->total() }}</span> produk
                </p>

                {{-- HP: cukup Sebelumnya / Berikutnya; nomor halaman tampil mulai tablet --}}
                <div class="flex flex-wrap items-center justify-center gap-1">
                    @if ($products->onFirstPage())
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 font-label-sm text-label-sm text-outline opacity-50">Sebelumnya</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="inline-flex items-center rounded-lg bg-surface-container-low px-2.5 py-1.5 font-label-sm text-label-sm text-on-surface hover:bg-surface-container">Sebelumnya</a>
                    @endif

                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        <a href="{{ $url }}"
                           @class([
                               'tabular hidden h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 font-label-sm text-label-sm transition sm:inline-flex',
                               'bg-primary-container text-on-primary shadow-sm' => $page === $products->currentPage(),
                               'bg-surface-container-low text-on-surface-variant hover:bg-surface-container' => $page !== $products->currentPage(),
                           ])>
                            {{ $page }}
                        </a>
                    @endforeach

                    @if ($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="inline-flex items-center rounded-lg bg-surface-container-low px-2.5 py-1.5 font-label-sm text-label-sm text-on-surface hover:bg-surface-container">Berikutnya</a>
                    @else
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 font-label-sm text-label-sm text-outline opacity-50">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-surface-container-lowest px-space-md py-space-2xl text-center shadow-[0_1px_3px_rgba(30,27,75,0.04)]">
            <div class="mb-space-md flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-container-low text-primary">
                <span class="material-symbols-outlined text-[32px]">inventory_2</span>
            </div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                {{ ($search !== '' || $selectedCategory !== '') ? 'Produk tidak ditemukan' : 'Belum ada produk digital' }}
            </h2>
            <p class="mt-1 max-w-md font-body-md text-body-md text-on-surface-variant">
                @if ($search !== '' || $selectedCategory !== '')
                    Tidak ada produk aktif yang cocok dengan pencarian atau filter yang dipilih.
                @else
                    Saat ini belum ada produk digital aktif yang tersedia di katalog.
                @endif
            </p>
            @if ($search !== '' || $selectedCategory !== '')
                <a href="{{ route('shop.products.index') }}"
                   class="mt-space-md inline-flex items-center gap-1.5 rounded-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-blue-500 active:bg-blue-600">
                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                    <span>Tampilkan Semua Produk</span>
                </a>
            @endif
        </div>
    @endif

    <x-toast />
</x-layouts.shop>

