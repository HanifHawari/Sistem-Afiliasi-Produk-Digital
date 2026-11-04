@php
    $categoryIcon = fn (?string $category) => match (true) {
        str_contains(strtolower((string) $category), 'course') => 'smart_display',
        str_contains(strtolower((string) $category), 'ebook'), str_contains(strtolower((string) $category), 'pdf') => 'menu_book',
        str_contains(strtolower((string) $category), 'template') => 'dashboard_customize',
        str_contains(strtolower((string) $category), 'software') => 'terminal',
        default => 'inventory_2',
    };

    $oldForm = old('_form') === 'product' ? collect(old())->except(['_token', '_method', 'thumbnail', 'file'])->all() : null;
@endphp

<x-layouts.admin title="Produk & Komisi">
    <div x-data="productPage({
            storeUrl: @js(route('admin.products.store')),
            updateUrl: @js(route('admin.products.update', '__ID__')),
            downloadUrl: @js(route('admin.products.download', '__ID__')),
            old: @js($oldForm),
         })">

        {{-- Header & aksi cepat --}}
        <div class="mb-space-lg flex flex-col justify-between gap-space-md rounded-xl bg-surface-container-lowest p-space-md shadow-sm sm:p-space-lg lg:flex-row lg:items-center">
            <div class="flex max-w-2xl flex-col">
                <div class="mb-1.5 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-2.5 py-0.5 font-label-sm text-label-sm text-on-primary-fixed-variant">
                        <span class="material-symbols-outlined text-[14px]">tune</span> Admin Control Center
                    </span>
                    <span class="font-caption text-caption text-on-surface-variant">{{ $stats['active_products'] }} produk aktif</span>
                </div>
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile tracking-tight text-on-surface md:font-headline-lg md:text-headline-lg">Manajemen Produk Digital &amp; Skema Komisi</h1>
                <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                    Atur harga jual, persentase bagi hasil komisi afiliator, dan materi promosi untuk setiap produk.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-space-sm [&>*]:flex-1 [&>*]:justify-center [&>*]:whitespace-nowrap sm:[&>*]:flex-none">
                <a href="{{ route('admin.payment-accounts.index') }}" class="flex items-center gap-2 rounded-lg bg-surface-container-low px-space-md py-2.5 font-label-md text-label-md text-on-surface transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-[18px]">account_balance</span> Atur Rekening (QRIS/Bank)
                </a>
                <button type="button" @click="create()" class="flex items-center gap-2 rounded-lg bg-primary px-space-md py-2.5 font-label-md text-label-md text-on-primary shadow-sm transition-all hover:opacity-95 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span> Tambah Produk Digital
                </button>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="mb-space-lg grid grid-cols-2 gap-3 sm:gap-space-md xl:grid-cols-4">
            <x-stat-card label="Total Produk Aktif" icon="inventory_2" :value="$stats['active_products'].' Produk'" caption="Katalog digital tersedia">
                <span class="flex items-center gap-1.5 text-secondary">
                    <span class="material-symbols-outlined text-[16px]">trending_up</span>
                    +{{ $stats['new_products_month'] }} produk bulan ini
                </span>
            </x-stat-card>
            <x-stat-card label="Total Afiliator Terdaftar" icon="group" tone="secondary" :value="$stats['affiliates'].' Afiliator'" caption="Mitra yang bisa mempromosikan produk">
                <span class="flex items-center gap-1.5 text-secondary">
                    <span class="material-symbols-outlined text-[16px]">person_add</span>
                    +{{ $stats['new_affiliates_week'] }} pendaftar minggu ini
                </span>
            </x-stat-card>
            <x-stat-card label="Total Komisi Dibayarkan" icon="paid" tone="tertiary" value-class="text-secondary" :value="rupiah($stats['commission_paid'])" caption="Akumulasi komisi yang sudah dicairkan" />
            <x-stat-card label="Antrean Pencairan" icon="hourglass_top" highlight value-class="text-primary"
                :value="rupiah($stats['withdrawal_pending_sum'])" :caption="$stats['withdrawal_pending_count'].' permintaan butuh persetujuan'">
                @if ($stats['withdrawal_pending_count'] > 0)
                    <x-badge tone="info" pulse class="bg-surface-container-lowest">Perlu Verifikasi</x-badge>
                @else
                    <span class="text-on-surface-variant">Tidak ada antrean</span>
                @endif
                <a href="#antrean-pencairan" class="text-primary hover:underline">Tinjau →</a>
            </x-stat-card>
        </div>

        <div class="mb-space-lg grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
            {{-- Tabel produk --}}
            <div class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm xl:col-span-8">
                <div class="flex flex-col justify-between gap-space-sm p-space-md sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Daftar Produk &amp; Distribusi Margin</h2>
                        <p class="font-caption text-caption text-on-surface-variant">Bagi hasil per unit produk digital yang diterbitkan</p>
                    </div>
                    <form method="GET" action="{{ route('admin.products.index') }}" class="relative">
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline">search</span>
                        <input name="q" value="{{ $search }}" type="text" placeholder="Cari nama produk..."
                               class="rounded-lg border-0 bg-surface-container-low py-1.5 pl-8 pr-3 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:bg-surface-container focus:ring-0">
                    </form>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="ledger w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                                <th class="px-space-md py-3 font-semibold">Produk &amp; Penjualan</th>
                                <th class="px-space-md py-3 text-right font-semibold">Harga Jual</th>
                                <th class="px-space-md py-3 text-center font-semibold">Bagi Komisi</th>
                                <th class="px-space-md py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low font-body-md text-body-md">
                            @forelse ($products as $product)
                                <tr class="transition-colors hover:bg-surface-container-low/60">
                                    <td class="min-w-[240px] px-space-md py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($product->thumbnail)
                                                <img src="{{ asset('storage/'.$product->thumbnail) }}" alt="" class="hidden h-11 w-11 shrink-0 rounded-lg object-cover sm:block">
                                            @else
                                                <div class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-surface-container-low text-primary sm:flex">
                                                    <span class="material-symbols-outlined">{{ $categoryIcon($product->category) }}</span>
                                                </div>
                                            @endif
                                            <div class="flex min-w-0 flex-col gap-0.5">
                                                <div class="flex items-center gap-2">
                                                    <span @class(['line-clamp-2 font-label-md text-label-md', 'text-on-surface' => $product->is_active, 'text-outline' => ! $product->is_active])>{{ $product->name }}</span>
                                                    @unless ($product->is_active)
                                                        <x-badge tone="neutral" :dot="false" class="!py-0">Nonaktif</x-badge>
                                                    @endunless
                                                </div>
                                                <span class="flex flex-wrap items-center gap-x-1 font-caption text-caption text-on-surface-variant">
                                                    <span class="material-symbols-outlined text-[14px] text-primary">{{ $categoryIcon($product->category) }}</span>
                                                    {{ $product->category ?? 'Tanpa kategori' }}
                                                    · <span class="tabular">{{ $product->sold_count }} terjual ({{ rupiah($product->revenue) }})</span>
                                                </span>
                                                @if (! $product->file_path)
                                                    <span class="flex items-center gap-1 font-caption text-caption text-error">
                                                        <span class="material-symbols-outlined text-[14px]">warning</span> Belum ada file produk
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tabular whitespace-nowrap px-space-md py-3 text-right font-medium text-on-surface">{{ rupiah($product->price) }}</td>
                                    <td class="px-space-md py-3">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="flex items-center gap-2 whitespace-nowrap">
                                                <span class="inline-flex rounded-full bg-secondary-fixed px-2 py-0.5 font-label-sm text-label-sm text-on-secondary-fixed">Afiliasi {{ (float) $product->commission_rate }}%</span>
                                                <span class="font-caption text-caption text-on-surface-variant">Owner {{ 100 - (float) $product->commission_rate }}%</span>
                                            </div>
                                            <span class="tabular font-caption text-caption font-medium text-secondary">{{ rupiah($product->commissionAmount()) }} / unit</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-space-md py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" title="Edit harga &amp; komisi" class="rounded-lg p-1.5 text-primary transition-colors hover:bg-primary-fixed"
                                                    @click="edit(@js($product->only(['id', 'name', 'category', 'price', 'description', 'is_active']) + ['commission_rate' => (float) $product->commission_rate, 'has_file' => (bool) $product->file_path]))">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <a href="{{ route('admin.products.promo-materials.index', $product->id) }}" title="Materi promosi ({{ $product->promo_materials_count }})" class="relative rounded-lg p-1.5 text-on-surface-variant transition-colors hover:bg-surface-container">
                                                <span class="material-symbols-outlined text-[18px]">photo_library</span>
                                                @if ($product->promo_materials_count)
                                                    <span class="absolute -right-0.5 -top-0.5 rounded-full bg-primary px-1 font-caption text-[10px] text-on-primary">{{ $product->promo_materials_count }}</span>
                                                @endif
                                            </a>
                                            <form method="POST" action="{{ route('admin.products.toggle', $product->id) }}">
                                                @csrf @method('PATCH')
                                                <button title="{{ $product->is_active ? 'Nonaktifkan produk' : 'Aktifkan produk' }}"
                                                        @class(['rounded-lg p-1.5 transition-colors', 'text-error hover:bg-error-container' => $product->is_active, 'text-secondary hover:bg-secondary-fixed' => ! $product->is_active])>
                                                    <span class="material-symbols-outlined text-[18px]">power_settings_new</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-space-md py-space-2xl text-center text-on-surface-variant">
                                        <span class="material-symbols-outlined mb-1 block text-[36px] text-outline">inventory_2</span>
                                        {{ $search ? 'Produk "'.$search.'" tidak ditemukan.' : 'Belum ada produk. Klik "Tambah Produk Digital".' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between bg-surface-container-low p-space-sm text-on-surface-variant">
                    <span class="font-caption text-caption">Menampilkan {{ $products->count() }} dari {{ $products->total() }} produk terdaftar</span>
                    @if ($products->hasPages())
                        <div class="flex items-center gap-1">
                            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                <a href="{{ $url }}" @class([
                                    'rounded px-2 py-1 font-caption text-caption',
                                    'bg-surface-container-lowest font-medium text-on-surface shadow-sm' => $page === $products->currentPage(),
                                    'hover:bg-surface-container-lowest' => $page !== $products->currentPage(),
                                ])>{{ $page }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-space-md xl:col-span-4">
                {{-- Simulator bagi hasil --}}
                <div class="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px] text-primary">calculate</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Simulasi Bagi Hasil</h3>
                        </div>
                        <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold uppercase text-on-primary-fixed">Simulator</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Hitung pendapatan pemilik produk vs komisi afiliator sebelum menerbitkan produk.</p>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label for="sim-price" class="font-label-sm text-label-sm text-on-surface-variant">Harga Jual Produk</label>
                            <span class="font-label-md text-label-md font-bold text-primary" x-text="rupiah(sim.price)"></span>
                        </div>
                        <input id="sim-price" type="range" min="20000" max="1000000" step="5000" x-model.number="sim.price" class="h-2 w-full cursor-pointer rounded-lg bg-surface-container-high accent-primary">
                        <div class="flex justify-between font-caption text-caption text-outline"><span>Rp 20.000</span><span>Rp 1.000.000</span></div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label for="sim-rate" class="font-label-sm text-label-sm text-on-surface-variant">Persentase Komisi Afiliasi</label>
                            <span class="font-label-md text-label-md font-bold text-secondary" x-text="sim.rate + '%'"></span>
                        </div>
                        <input id="sim-rate" type="range" min="10" max="80" step="5" x-model.number="sim.rate" class="h-2 w-full cursor-pointer rounded-lg bg-surface-container-high accent-secondary">
                        <div class="flex justify-between font-caption text-caption text-outline"><span>10% (Konservatif)</span><span>80% (Agresif)</span></div>
                    </div>

                    <div class="mt-1 flex flex-col gap-space-sm rounded-xl bg-surface-container-low p-space-md">
                        <span class="font-caption text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Pembagian per 1 penjualan:</span>
                        <div class="flex h-3 w-full overflow-hidden rounded-full bg-surface-container-high">
                            <div class="h-full bg-primary transition-all duration-300" :style="`width: ${100 - sim.rate}%`"></div>
                            <div class="h-full bg-secondary transition-all duration-300" :style="`width: ${sim.rate}%`"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-space-sm pt-2">
                            <div class="flex flex-col rounded-lg bg-surface-container-lowest p-2.5 shadow-sm">
                                <span class="flex items-center gap-1 font-caption text-caption text-on-surface-variant"><span class="h-2 w-2 rounded-full bg-primary"></span> Product Owner</span>
                                <span class="tabular mt-1 font-currency-metric text-[1.15rem] font-bold leading-snug text-primary" x-text="rupiah(sim.price * (100 - sim.rate) / 100)"></span>
                                <span class="font-caption text-caption text-outline" x-text="(100 - sim.rate) + '% porsi pemilik'"></span>
                            </div>
                            <div class="flex flex-col rounded-lg bg-surface-container-lowest p-2.5 shadow-sm">
                                <span class="flex items-center gap-1 font-caption text-caption text-on-surface-variant"><span class="h-2 w-2 rounded-full bg-secondary"></span> Komisi Afiliator</span>
                                <span class="tabular mt-1 font-currency-metric text-[1.15rem] font-bold leading-snug text-secondary" x-text="rupiah(sim.price * sim.rate / 100)"></span>
                                <span class="font-caption text-caption text-outline" x-text="sim.rate + '% porsi mitra'"></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-surface-container p-2.5">
                            <span class="font-caption text-caption text-on-surface-variant">Potensi afiliator (100 penjualan)</span>
                            <span class="tabular font-label-md text-label-md font-bold text-on-surface" x-text="rupiah(sim.price * sim.rate)"></span>
                        </div>
                    </div>

                    <button type="button" @click="create({ price: sim.price, commission_rate: sim.rate })"
                            class="rounded-lg bg-primary-fixed py-2.5 font-label-md text-label-md text-on-primary-fixed-variant transition-colors hover:bg-primary-fixed-dim">
                        Gunakan Skema Ini ke Produk Baru
                    </button>
                </div>

                {{-- Materi promosi --}}
                <div class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Paket Materi Promosi</h3>
                        <span class="material-symbols-outlined text-on-surface-variant">folder_open</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Sediakan gambar, video, dan caption siap pakai agar afiliator lebih cepat berjualan.</p>
                    <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-space-sm">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">perm_media</span>
                            <span class="font-label-sm text-label-sm text-on-surface">{{ $stats['promo_materials'] }} materi tersedia</span>
                        </div>
                        <span class="font-caption text-caption text-on-surface-variant">Klik <span class="material-symbols-outlined align-middle text-[14px]">photo_library</span> di tabel</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Antrean pencairan --}}
        <div id="antrean-pencairan" class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
            <div class="flex flex-col justify-between gap-space-sm p-space-lg sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface">Antrean Pengajuan Pencairan Afiliator</h2>
                        @if ($stats['withdrawal_pending_count'] > 0)
                            <span class="rounded-full bg-error-container px-2 py-0.5 font-caption text-caption text-on-error-container">{{ $stats['withdrawal_pending_count'] }} menunggu tindakan</span>
                        @endif
                    </div>
                    <p class="font-caption text-caption text-on-surface-variant">Pastikan saldo komisi valid sebelum dana ditransfer ke rekening afiliator.</p>
                </div>
                <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center gap-1.5 self-start rounded-lg bg-secondary px-space-md py-2 font-label-sm text-label-sm text-on-secondary shadow-sm hover:bg-secondary/90 sm:self-auto">
                    <span class="material-symbols-outlined text-[16px]">done_all</span> Proses Pencairan
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="ledger w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low font-caption text-caption uppercase tracking-wider text-on-surface-variant">
                            <th class="px-space-md py-3">Afiliator</th>
                            <th class="px-space-md py-3 text-right">Nominal</th>
                            <th class="px-space-md py-3">Tujuan Rekening</th>
                            <th class="px-space-md py-3">Waktu Pengajuan</th>
                            <th class="px-space-md py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        @forelse ($pendingWithdrawals as $withdrawal)
                            <tr class="hover:bg-surface-container-low/60">
                                <td class="px-space-md py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-fixed font-label-sm text-label-sm text-on-primary-fixed-variant">{{ str($withdrawal->affiliate->name)->substr(0, 1)->upper() }}</span>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface">{{ $withdrawal->affiliate->name }}</p>
                                            <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->affiliate->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="tabular whitespace-nowrap px-space-md py-3 text-right font-label-md text-label-md text-secondary">{{ rupiah($withdrawal->amount) }}</td>
                                <td class="px-space-md py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded bg-primary-fixed px-2 py-0.5 font-caption text-caption font-semibold text-primary">{{ $withdrawal->bank_name }}</span>
                                        <div>
                                            <p class="tabular font-label-sm text-label-sm text-on-surface">{{ $withdrawal->account_number }}</p>
                                            <p class="font-caption text-caption text-on-surface-variant">a.n {{ $withdrawal->account_holder }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-space-md py-3">
                                    <p class="font-label-sm text-label-sm text-on-surface">{{ $withdrawal->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
                                    <p class="font-caption text-caption text-on-surface-variant">{{ $withdrawal->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-space-md py-3 text-center">
                                    <x-badge :tone="$withdrawal->status->tone()">{{ $withdrawal->status->label() }}</x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-space-md py-space-xl text-center text-on-surface-variant">Belum ada pengajuan pencairan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal tambah / edit produk --}}
        <x-modal name="product-form" :show="$oldForm !== null" max-width="lg" focusable>
            <form method="POST" :action="action" enctype="multipart/form-data" class="flex flex-col gap-space-md p-space-lg">
                @csrf
                <input type="hidden" name="_form" value="product">
                <input type="hidden" name="_product_id" :value="form._product_id">
                <input type="hidden" name="_has_file" :value="form.has_file ? 1 : 0">
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[24px] text-primary" x-text="mode === 'edit' ? 'edit_note' : 'post_add'"></span>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface" x-text="mode === 'edit' ? 'Edit Produk' : 'Tambah Produk Digital Baru'"></h3>
                    </div>
                    <button type="button" class="text-on-surface-variant hover:text-on-surface" @click="$dispatch('close-modal', 'product-form')">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-3">
                    <div>
                        <label for="name" class="font-label-sm text-label-sm text-on-surface">Nama Produk Digital</label>
                        <input id="name" name="name" type="text" x-model="form.name" placeholder="Contoh: Notion Freelance Suite 2025" required
                               class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 text-on-surface focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="category" class="font-label-sm text-label-sm text-on-surface">Kategori</label>
                            <input id="category" name="category" type="text" list="category-options" x-model="form.category" placeholder="E-Course, Ebook, ..."
                                   class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 text-on-surface focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                            <datalist id="category-options">
                                @foreach ($categories->merge(['E-Course', 'Ebook', 'Template', 'Software / License Key'])->unique() as $category)
                                    <option value="{{ $category }}">
                                @endforeach
                            </datalist>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>
                        <div>
                            <label for="price" class="font-label-sm text-label-sm text-on-surface">Harga Jual (Rp)</label>
                            <input id="price" name="price" type="number" min="1000" step="500" x-model.number="form.price" placeholder="150000" required
                                   class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 text-on-surface focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                            <x-input-error :messages="$errors->get('price')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="commission_rate" class="font-label-sm text-label-sm text-on-surface">Persentase Komisi (%)</label>
                            <input id="commission_rate" name="commission_rate" type="number" min="0" max="100" step="0.5" x-model.number="form.commission_rate" required
                                   class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 text-on-surface focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                            <x-input-error :messages="$errors->get('commission_rate')" class="mt-1" />
                        </div>
                        <div>
                            <span class="font-label-sm text-label-sm text-on-surface">Komisi Afiliator</span>
                            <div class="tabular mt-1 rounded-lg bg-surface-container-high px-3 py-2 font-label-md text-label-md text-secondary"
                                 x-text="rupiah((form.price || 0) * (form.commission_rate || 0) / 100) + ' / unit'"></div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="font-label-sm text-label-sm text-on-surface">Deskripsi</label>
                        <textarea id="description" name="description" rows="3" x-model="form.description" placeholder="Jelaskan isi dan manfaat produk"
                                  class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 text-on-surface focus:bg-surface-container focus:ring-2 focus:ring-primary-container"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="thumbnail" class="font-label-sm text-label-sm text-on-surface">Thumbnail (gambar, maks. 2 MB)</label>
                            <input id="thumbnail" name="thumbnail" type="file" accept="image/*"
                                   class="mt-1 w-full font-body-sm text-body-sm text-on-surface-variant file:mr-2 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-1.5 file:font-label-sm file:text-on-primary-fixed-variant">
                            <x-input-error :messages="$errors->get('thumbnail')" class="mt-1" />
                        </div>
                        <div>
                            <label for="file" class="font-label-sm text-label-sm text-on-surface">File Produk (maks. 50 MB)</label>
                            <input id="file" name="file" type="file"
                                   class="mt-1 w-full font-body-sm text-body-sm text-on-surface-variant file:mr-2 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-1.5 file:font-label-sm file:text-on-primary-fixed-variant">
                            <p class="mt-1 font-caption text-caption text-on-surface-variant" x-show="mode === 'edit'"
                               x-text="form.has_file ? 'Sudah ada file. Kosongkan jika tidak diganti.' : 'Belum ada file produk.'"></p>
                            <x-input-error :messages="$errors->get('file')" class="mt-1" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-primary-container focus:ring-primary-container">
                        <span class="font-label-sm text-label-sm text-on-surface">Aktif (tampil di katalog customer & afiliator)</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <div class="flex items-center gap-space-md">
                        <template x-if="mode === 'edit'">
                            <button type="submit" form="delete-product-form" class="flex items-center gap-1 font-label-sm text-label-sm text-error hover:underline"
                                    onclick="return confirm('Hapus produk ini? Produk yang sudah punya transaksi tidak bisa dihapus, nonaktifkan saja.')">
                                <span class="material-symbols-outlined text-[16px]">delete</span> Hapus
                            </button>
                        </template>
                        <template x-if="mode === 'edit' && form.has_file">
                            <a :href="config.downloadUrl.replace('__ID__', form._product_id)" class="flex items-center gap-1 font-label-sm text-label-sm text-primary hover:underline">
                                <span class="material-symbols-outlined text-[16px]">download</span> Unduh file
                            </a>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="$dispatch('close-modal', 'product-form')" class="rounded-lg bg-surface-container-low px-4 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container">Batal</button>
                        <button class="rounded-lg bg-primary px-4 py-2 font-label-md text-label-md text-on-primary shadow-sm hover:opacity-95"
                                x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Terbitkan Produk'"></button>
                    </div>
                </div>
            </form>

            {{-- Form hapus terpisah (form tidak boleh bersarang), dipicu tombol "Hapus" di atas --}}
            <form id="delete-product-form" method="POST" :action="config.updateUrl.replace('__ID__', form._product_id)" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </x-modal>
    </div>

    <script>
        function productPage(config) {
            const blank = { _product_id: '', name: '', category: '', price: '', commission_rate: 40, description: '', is_active: true, has_file: false };
            const old = config.old;

            return {
                config,
                mode: old && old._product_id ? 'edit' : 'create',
                form: old ? { ...blank, ...old, is_active: old.is_active === '1', has_file: old._has_file === '1' } : { ...blank },
                sim: { price: 100000, rate: 40 },

                get action() {
                    return this.mode === 'edit' ? config.updateUrl.replace('__ID__', this.form._product_id) : config.storeUrl;
                },
                create(prefill = {}) {
                    this.mode = 'create';
                    this.form = { ...blank, ...prefill };
                    this.$dispatch('open-modal', 'product-form');
                },
                edit(product) {
                    this.mode = 'edit';
                    this.form = { ...blank, ...product, _product_id: product.id, description: product.description ?? '', category: product.category ?? '' };
                    this.$dispatch('open-modal', 'product-form');
                },
                rupiah(value) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
                },
            };
        }
    </script>
</x-layouts.admin>
