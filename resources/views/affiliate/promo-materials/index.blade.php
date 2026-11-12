@php
    use App\Enums\PromoMaterialType;
    use Illuminate\Support\Facades\Storage;

    $typeOptions = [
        '' => ['label' => 'Semua Tipe', 'icon' => 'apps'],
        PromoMaterialType::Image->value => ['label' => 'Gambar', 'icon' => 'image'],
        PromoMaterialType::Video->value => ['label' => 'Video', 'icon' => 'videocam'],
        PromoMaterialType::Text->value => ['label' => 'Teks / Caption', 'icon' => 'notes'],
    ];

    $categoryIcon = fn (?string $category) => match (true) {
        str_contains(strtolower((string) $category), 'course') => 'smart_display',
        str_contains(strtolower((string) $category), 'ebook'), str_contains(strtolower((string) $category), 'pdf') => 'menu_book',
        str_contains(strtolower((string) $category), 'template') => 'dashboard_customize',
        str_contains(strtolower((string) $category), 'software') => 'terminal',
        str_contains(strtolower((string) $category), 'asset'), str_contains(strtolower((string) $category), 'preset') => 'palette',
        default => 'inventory_2',
    };
@endphp

<x-layouts.affiliate title="Materi Promosi">

    {{-- ================================================================
         1. PAGE HEADER
         ================================================================ --}}
    <x-page-header
        eyebrow="Ruang Afiliator"
        title="Materi Promosi"
        description="Temukan dan gunakan materi promosi produk untuk membantu meningkatkan penjualan referral Anda."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3.5 py-1.5 font-mono text-xs font-semibold text-blue-700 ring-1 ring-blue-200">
                <span class="material-symbols-outlined text-[16px]">perm_media</span>
                <span>{{ $materials->total() }} Materi Tersedia</span>
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-6" data-enter="up">

        {{-- ================================================================
             2. TOOLBAR FILTER & PENCARIAN
             ================================================================ --}}
        <form method="GET" action="{{ route('affiliate.promo-materials.index') }}" class="toolbar">

            {{-- Filter Tipe Materi (Pills / Tabs) --}}
            <div class="tabs no-scrollbar">
                @foreach ($typeOptions as $typeKey => $meta)
                    <a href="{{ route('affiliate.promo-materials.index', array_filter(['type' => $typeKey, 'product_id' => $selectedProduct, 'q' => $search])) }}"
                       @class([
                           'shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition',
                           'bg-blue-600 text-white shadow-sm shadow-blue-600/20' => (string) $selectedType === (string) $typeKey,
                           'text-slate-600 hover:bg-slate-100' => (string) $selectedType !== (string) $typeKey,
                       ])>
                        <span class="material-symbols-outlined text-[16px]">{{ $meta['icon'] }}</span>
                        <span>{{ $meta['label'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Hidden input tipe untuk form submit --}}
            @if ($selectedType)
                <input type="hidden" name="type" value="{{ $selectedType }}">
            @endif

            {{-- Dropdown Filter Produk --}}
            <select
                name="product_id"
                onchange="this.form.submit()"
                class="input w-full sm:w-56"
            >
                <option value="">Semua Produk</option>
                @foreach ($products as $productOption)
                    <option value="{{ $productOption->id }}" @selected((string) $selectedProduct === (string) $productOption->id)>
                        {{ $productOption->name }}
                    </option>
                @endforeach
            </select>

            {{-- Input Pencarian --}}
            <div class="search lg:ml-auto">
                <span class="material-symbols-outlined">search</span>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari judul materi, caption, atau nama produk..."
                >
            </div>

            <button type="submit" class="btn-primary btn-sm shrink-0">
                Cari
            </button>

            @if ($search !== '' || ! empty($selectedProduct) || ! empty($selectedType))
                <a href="{{ route('affiliate.promo-materials.index') }}" class="btn-outline btn-sm shrink-0" title="Reset filter">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    <span class="hidden sm:inline">Reset</span>
                </a>
            @endif

        </form>

        {{-- ================================================================
             3. GRID MATERI PROMOSI
             ================================================================ --}}
        @if ($materials->isNotEmpty())
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($materials as $material)
                    @php
                        $product = $material->product;
                        $userLink = $userLinks->get($material->product_id);
                        $referralUrl = $userLink?->url();
                        $hasCaption = filled($material->caption);
                        $captionText = (string) $material->caption;
                        $fileExists = filled($material->file_path) && Storage::disk('public')->exists($material->file_path);
                        $mediaUrl = $material->file_path ? asset('storage/' . $material->file_path) : null;

                        // Penggabungan Caption + Referral Link
                        $combinedText = '';
                        if ($referralUrl) {
                            $trimmedCaption = trim($captionText);
                            if ($trimmedCaption === '') {
                                $combinedText = $referralUrl;
                            } elseif (str_contains($trimmedCaption, $referralUrl)) {
                                $combinedText = $trimmedCaption;
                            } else {
                                $combinedText = $trimmedCaption . "\n\n" . $referralUrl;
                            }
                        }

                        $typeBadge = match ($material->type) {
                            PromoMaterialType::Image => ['label' => 'Gambar', 'icon' => 'image', 'class' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/80'],
                            PromoMaterialType::Video => ['label' => 'Video', 'icon' => 'videocam', 'class' => 'bg-purple-50 text-purple-700 ring-1 ring-purple-200/80'],
                            PromoMaterialType::Text => ['label' => 'Teks / Caption', 'icon' => 'notes', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/80'],
                        };
                    @endphp

                    <div
                        x-data="{
                            copiedType: null,
                            failedType: null,
                            copy(text, type) {
                                if (!text) return;
                                const self = this;
                                if (navigator.clipboard && window.isSecureContext) {
                                    navigator.clipboard.writeText(text)
                                        .then(() => self.triggerSuccess(type))
                                        .catch(() => self.fallbackCopy(text, type));
                                } else {
                                    self.fallbackCopy(text, type);
                                }
                            },
                            fallbackCopy(text, type) {
                                try {
                                    const textarea = document.createElement('textarea');
                                    textarea.value = text;
                                    textarea.style.position = 'fixed';
                                    textarea.style.left = '-9999px';
                                    textarea.style.top = '0';
                                    document.body.appendChild(textarea);
                                    textarea.focus();
                                    textarea.select();
                                    const successful = document.execCommand('copy');
                                    document.body.removeChild(textarea);
                                    if (successful) {
                                        this.triggerSuccess(type);
                                    } else {
                                        this.triggerError(type);
                                    }
                                } catch (err) {
                                    this.triggerError(type);
                                }
                            },
                            triggerSuccess(type) {
                                this.copiedType = type;
                                this.failedType = null;
                                setTimeout(() => { if (this.copiedType === type) this.copiedType = null; }, 2000);
                            },
                            triggerError(type) {
                                this.failedType = type;
                                this.copiedType = null;
                                setTimeout(() => { if (this.failedType === type) this.failedType = null; }, 2500);
                            }
                        }"
                        class="card group flex flex-col justify-between overflow-hidden transition hover:shadow-md hover:ring-1 hover:ring-blue-200"
                    >
                        {{-- ATAS: Preview Media & Detail Materi --}}
                        <div>
                            {{-- Area Preview Berdasarkan Tipe Materi --}}
                            @if ($material->type === PromoMaterialType::Image)
                                <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
                                    @if ($fileExists)
                                        <img
                                            src="{{ $mediaUrl }}"
                                            alt="{{ $material->title }}"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 bg-slate-100 p-4 text-center text-slate-400">
                                            <span class="material-symbols-outlined text-[36px]">broken_image</span>
                                            <span class="text-xs font-medium">Gambar tidak ditemukan di server</span>
                                        </div>
                                    @endif

                                    {{-- Badge Tipe & Kategori --}}
                                    <div class="absolute left-3 top-3 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-sm backdrop-blur-sm {{ $typeBadge['class'] }}">
                                            <span class="material-symbols-outlined text-[14px]">{{ $typeBadge['icon'] }}</span>
                                            {{ $typeBadge['label'] }}
                                        </span>
                                    </div>
                                </div>

                            @elseif ($material->type === PromoMaterialType::Video)
                                <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-900">
                                    @if ($fileExists)
                                        <video
                                            controls
                                            preload="metadata"
                                            playsinline
                                            class="h-full w-full object-contain bg-black"
                                        >
                                            <source src="{{ $mediaUrl }}">
                                            Browser Anda tidak mendukung pemutar video.
                                        </video>
                                    @else
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 bg-slate-900 p-4 text-center text-slate-400">
                                            <span class="material-symbols-outlined text-[36px]">videocam_off</span>
                                            <span class="text-xs font-medium">Video tidak ditemukan di server</span>
                                        </div>
                                    @endif

                                    {{-- Badge Tipe --}}
                                    <div class="pointer-events-none absolute left-3 top-3 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-sm backdrop-blur-sm {{ $typeBadge['class'] }}">
                                            <span class="material-symbols-outlined text-[14px]">{{ $typeBadge['icon'] }}</span>
                                            {{ $typeBadge['label'] }}
                                        </span>
                                    </div>
                                </div>

                            @else
                                {{-- Tipe Teks / Caption --}}
                                <div class="relative flex min-h-[120px] flex-col justify-center border-b border-slate-100 bg-gradient-to-br from-amber-50/60 via-slate-50 to-blue-50/30 p-5">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold shadow-sm backdrop-blur-sm {{ $typeBadge['class'] }}">
                                            <span class="material-symbols-outlined text-[14px]">{{ $typeBadge['icon'] }}</span>
                                            {{ $typeBadge['label'] }}
                                        </span>
                                        <span class="material-symbols-outlined text-[28px] text-amber-300/80">format_quote</span>
                                    </div>
                                    <p class="mt-3 text-xs italic text-slate-500">Materi copywriting siap posting untuk sosial media / chat.</p>
                                </div>
                            @endif

                            {{-- Informasi Materi & Produk --}}
                            <div class="card-body">
                                {{-- Kategori & Nama Produk --}}
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 font-semibold text-xs text-blue-600 truncate">
                                        <span class="material-symbols-outlined text-[15px]">{{ $categoryIcon($product->category) }}</span>
                                        <span class="truncate">{{ $product->name }}</span>
                                    </span>
                                    @if ($product->category)
                                        <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                            {{ $product->category }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Judul Materi --}}
                                <h3 class="mt-1.5 text-base font-bold text-slate-900 group-hover:text-blue-600 transition">
                                    {{ $material->title }}
                                </h3>

                                {{-- Teks Caption / Preview Teks --}}
                                @if ($hasCaption)
                                    <div x-data="{ expanded: false }" class="mt-3">
                                        @if ($material->type !== PromoMaterialType::Text)
                                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Caption / Copywriting</p>
                                        @endif
                                        <div
                                            :class="expanded ? 'max-h-96' : 'line-clamp-4 max-h-28'"
                                            class="overflow-y-auto whitespace-pre-line rounded-xl border border-slate-100 bg-slate-50/80 p-3 text-xs leading-relaxed text-slate-700 font-sans transition-all"
                                        >
                                            {{ $material->caption }}
                                        </div>
                                        @if (mb_strlen($material->caption) > 130)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="mt-1.5 inline-flex items-center gap-0.5 text-[11px] font-semibold text-blue-600 hover:text-blue-700"
                                            >
                                                <span x-text="expanded ? 'Ciutkan' : 'Lihat Selengkapnya'"></span>
                                                <span class="material-symbols-outlined text-[14px]" x-text="expanded ? 'expand_less' : 'expand_more'"></span>
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                {{-- Status Referral Link --}}
                                <div class="mt-4">
                                    @if ($userLink)
                                        <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-2.5 text-xs">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                                                    <span class="font-semibold text-emerald-800 truncate">Link Referral Anda Aktif</span>
                                                </div>
                                                <span class="font-mono text-[11px] font-bold text-emerald-700 shrink-0">Kode: {{ $userLink->code }}</span>
                                            </div>
                                            <div class="mt-1.5 flex items-center gap-1 text-[11px] font-mono text-emerald-800/90 truncate">
                                                <span class="material-symbols-outlined text-[13px] shrink-0 text-emerald-600">link</span>
                                                <span class="truncate select-all" title="URL Referral">{{ $referralUrl }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between rounded-xl border border-amber-200/80 bg-amber-50/70 px-3 py-2.5 text-xs">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">link_off</span>
                                                <span class="font-medium text-amber-800 truncate">Belum ada link referral</span>
                                            </div>
                                            <a href="{{ route('affiliate.products.index') }}" class="font-semibold text-amber-900 underline hover:text-amber-950 shrink-0 inline-flex items-center gap-0.5">
                                                <span>Buat Link</span>
                                                <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- BAWAH: Tombol Aksi --}}
                        <div class="border-t border-slate-100 bg-white p-4 pt-3 flex flex-col gap-2">
                            {{-- Tombol Unduh File untuk Gambar & Video --}}
                            @if ($material->type !== PromoMaterialType::Text)
                                @if ($fileExists)
                                    <a
                                        href="{{ route('affiliate.promo-materials.download', $material) }}"
                                        class="btn-outline btn-sm flex w-full items-center justify-center gap-1.5 font-semibold text-slate-700 hover:text-blue-600 hover:border-blue-300"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">download</span>
                                        <span>Unduh File</span>
                                    </a>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        class="btn-outline btn-sm flex w-full items-center justify-center gap-1.5 opacity-50 cursor-not-allowed text-slate-400"
                                        title="File media fisik belum diunggah atau tidak ditemukan di penyimpanan server"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">download_off</span>
                                        <span>File Tidak Tersedia</span>
                                    </button>
                                @endif
                            @endif

                            {{-- Tombol Aksi Salin --}}
                            @if ($hasCaption && $userLink)
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="copy(@js($material->caption), 'caption')"
                                        class="btn-outline btn-sm inline-flex items-center justify-center gap-1 text-xs transition"
                                        :class="copiedType === 'caption' ? '!border-emerald-600 !bg-emerald-50 !text-emerald-700' : (failedType === 'caption' ? '!border-rose-600 !bg-rose-50 !text-rose-700' : 'text-slate-700')"
                                        title="Salin caption materi saja"
                                    >
                                        <span class="material-symbols-outlined text-[15px]" x-text="copiedType === 'caption' ? 'check' : (failedType === 'caption' ? 'error' : 'content_copy')"></span>
                                        <span x-text="copiedType === 'caption' ? 'Tersalin!' : (failedType === 'caption' ? 'Gagal' : 'Salin Caption')"></span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="copy(@js($combinedText), 'combined')"
                                        class="btn-primary btn-sm inline-flex items-center justify-center gap-1 text-xs transition"
                                        :class="copiedType === 'combined' ? '!bg-emerald-600 text-white' : (failedType === 'combined' ? '!bg-rose-600 text-white' : '')"
                                        title="Salin caption otomatis digabungkan dengan URL referral Anda"
                                    >
                                        <span class="material-symbols-outlined text-[15px]" x-text="copiedType === 'combined' ? 'check' : (failedType === 'combined' ? 'error' : 'share')"></span>
                                        <span x-text="copiedType === 'combined' ? 'Tersalin!' : (failedType === 'combined' ? 'Gagal' : 'Salin + Link Saya')"></span>
                                    </button>
                                </div>
                            @elseif ($hasCaption)
                                <button
                                    type="button"
                                    @click="copy(@js($material->caption), 'caption')"
                                    class="btn-outline btn-sm flex w-full items-center justify-center gap-1.5 text-xs transition text-slate-700"
                                    :class="copiedType === 'caption' ? '!border-emerald-600 !bg-emerald-50 !text-emerald-700' : (failedType === 'caption' ? '!border-rose-600 !bg-rose-50 !text-rose-700' : '')"
                                >
                                    <span class="material-symbols-outlined text-[15px]" x-text="copiedType === 'caption' ? 'check' : (failedType === 'caption' ? 'error' : 'content_copy')"></span>
                                    <span x-text="copiedType === 'caption' ? 'Caption Tersalin!' : (failedType === 'caption' ? 'Gagal Salin' : 'Salin Caption')"></span>
                                </button>
                            @elseif ($userLink)
                                <button
                                    type="button"
                                    @click="copy(@js($combinedText), 'combined')"
                                    class="btn-outline btn-sm flex w-full items-center justify-center gap-1.5 text-xs transition text-slate-700"
                                    :class="copiedType === 'combined' ? '!border-emerald-600 !bg-emerald-50 !text-emerald-700' : (failedType === 'combined' ? '!border-rose-600 !bg-rose-50 !text-rose-700' : '')"
                                    title="Salin URL link referral Anda"
                                >
                                    <span class="material-symbols-outlined text-[15px]" x-text="copiedType === 'combined' ? 'check' : (failedType === 'combined' ? 'error' : 'link')"></span>
                                    <span x-text="copiedType === 'combined' ? 'Link Tersalin!' : (failedType === 'combined' ? 'Gagal Salin' : 'Salin Link Saya')"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ================================================================
                 4. PAGINASI
                 ================================================================ --}}
            @if ($materials->hasPages())
                <div class="card">
                    <x-pager :paginator="$materials" noun="materi promosi" />
                </div>
            @endif

        @else
            {{-- ================================================================
                 5. EMPTY STATE
                 ================================================================ --}}
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-[36px]">perm_media</span>
                </div>
                <h3 class="mt-4 text-base font-bold text-slate-800">
                    {{ ($search !== '' || ! empty($selectedProduct) || ! empty($selectedType)) ? 'Materi Promosi Tidak Ditemukan' : 'Belum Ada Materi Promosi' }}
                </h3>
                <p class="mt-1 max-w-md text-xs text-slate-500">
                    @if ($search !== '' || ! empty($selectedProduct) || ! empty($selectedType))
                        Tidak ada materi promosi yang cocok dengan kriteria filter atau pencarian Anda. Coba kata kunci lain atau bersihkan filter.
                    @else
                        Saat ini belum ada materi promosi yang tersedia untuk produk aktif. Anda tetap dapat membagikan link produk melalui Katalog Produk.
                    @endif
                </p>

                <div class="mt-5 flex items-center gap-3">
                    @if ($search !== '' || ! empty($selectedProduct) || ! empty($selectedType))
                        <a href="{{ route('affiliate.promo-materials.index') }}" class="btn-outline btn-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                            Reset Pencarian &amp; Filter
                        </a>
                    @else
                        <a href="{{ route('affiliate.products.index') }}" class="btn-primary btn-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">storefront</span>
                            Buka Katalog Produk
                        </a>
                    @endif
                </div>
            </div>
        @endif

    </div>

</x-layouts.affiliate>
