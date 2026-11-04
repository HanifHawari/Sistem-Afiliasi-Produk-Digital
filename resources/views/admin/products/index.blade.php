@php
    $oldForm = old('_form') === 'product' ? collect(old())->except(['_token', '_method', 'thumbnail', 'file'])->all() : null;
@endphp

<x-layouts.admin title="Produk & Komisi">
    <div x-data="productPage({
            storeUrl: @js(route('admin.products.store')),
            updateUrl: @js(route('admin.products.update', '__ID__')),
            downloadUrl: @js(route('admin.products.download', '__ID__')),
            old: @js($oldForm),
         })">

        <x-page-header title="Produk & Komisi"
            description="Atur harga jual, persentase komisi afiliator, dan materi promosi setiap produk.">
            <x-slot name="actions">
                <a href="{{ route('admin.payment-accounts.index') }}" class="btn-outline">Atur Rekening</a>
                <button type="button" @click="create()" class="btn-primary">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Tambah Produk
                </button>
            </x-slot>
        </x-page-header>

        {{-- Statistik --}}
        <x-stat-grid>
            <x-stat-card label="Produk Aktif" :value="$stats['active_products'].' produk'" caption="Katalog digital tersedia">
                <span>+{{ $stats['new_products_month'] }} produk bulan ini</span>
            </x-stat-card>
            <x-stat-card label="Afiliator Terdaftar" :value="$stats['affiliates'].' afiliator'" caption="Mitra yang bisa mempromosikan produk">
                <span>+{{ $stats['new_affiliates_week'] }} pendaftar minggu ini</span>
            </x-stat-card>
            <x-stat-card label="Komisi Dibayarkan" :value="rupiah($stats['commission_paid'])" caption="Akumulasi komisi yang sudah dicairkan" />
            <x-stat-card label="Antrean Pencairan" :value="rupiah($stats['withdrawal_pending_sum'])" :caption="$stats['withdrawal_pending_count'].' permintaan butuh persetujuan'">
                <span class="text-white/80">{{ $stats['withdrawal_pending_count'] ? 'Segera proses' : 'Tidak ada antrean' }}</span>
                <a href="{{ route('admin.withdrawals.index') }}" class="font-bold text-white hover:underline">Tinjau</a>
            </x-stat-card>
        </x-stat-grid>

        <div class="mb-6 grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
            {{-- Tabel produk --}}
            <section class="card overflow-hidden 2xl:col-span-8">
                <div class="card-header flex-col sm:flex-row sm:items-center">
                    <div>
                        <h2 class="card-title">Daftar Produk</h2>
                        <p class="card-subtitle">Harga jual dan bagi hasil per produk</p>
                    </div>
                    <form method="GET" action="{{ route('admin.products.index') }}" class="search lg:w-60">
                        <span class="material-symbols-outlined">search</span>
                        <input name="q" value="{{ $search }}" type="text" placeholder="Cari nama produk...">
                    </form>
                </div>

                <div class="relative overflow-x-auto">
                    <table class="data-table ledger">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga Jual</th>
                                <th>Bagi Komisi</th>
                                <th><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td class="min-w-[220px]">
                                        <div class="flex items-center gap-2">
                                            <span @class(['font-medium', 'text-slate-800' => $product->is_active, 'text-slate-400' => ! $product->is_active])>{{ $product->name }}</span>
                                            @unless ($product->is_active)
                                                <span class="pill-gray">Nonaktif</span>
                                            @endunless
                                        </div>
                                        <span class="mt-0.5 block text-[13px] text-slate-400">
                                            {{ $product->category ?? 'Tanpa kategori' }} &bull; {{ $product->sold_count }} terjual ({{ rupiah($product->revenue) }})
                                        </span>
                                        @if (! $product->file_path)
                                            <span class="mt-1 block text-[13px] text-red-500">Belum ada file produk</span>
                                        @endif
                                    </td>
                                    <td class="tabular whitespace-nowrap font-bold text-slate-900">{{ rupiah($product->price) }}</td>
                                    <td class="whitespace-nowrap">
                                        <span class="pill-green">Afiliasi {{ (float) $product->commission_rate }}%</span>
                                        <span class="tabular mt-1.5 block text-[13px] text-emerald-600">{{ rupiah($product->commissionAmount()) }} / unit</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="ml-auto flex max-w-[12.5rem] flex-wrap items-center justify-end gap-2">
                                            <button type="button" class="btn-soft"
                                                    @click="edit(@js($product->only(['id', 'name', 'category', 'price', 'description', 'is_active']) + ['commission_rate' => (float) $product->commission_rate, 'has_file' => (bool) $product->file_path]))">Edit</button>
                                            <a href="{{ route('admin.products.promo-materials.index', $product->id) }}" class="btn-outline btn-sm">Promosi{{ $product->promo_materials_count ? ' ('.$product->promo_materials_count.')' : '' }}</a>
                                            <form method="POST" action="{{ route('admin.products.toggle', $product->id) }}">
                                                @csrf @method('PATCH')
                                                <button @class(['btn btn-sm', 'bg-red-50 text-red-600 hover:bg-red-100' => $product->is_active, 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' => ! $product->is_active])>
                                                    {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-500">
                                        {{ $search ? 'Produk "'.$search.'" tidak ditemukan.' : 'Belum ada produk. Klik "Tambah Produk".' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 sm:px-7">
                    <span class="text-sm text-slate-500">{{ $products->count() }} dari {{ $products->total() }} produk</span>
                    @if ($products->hasPages())
                        <div class="flex items-center gap-1">
                            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                <a href="{{ $url }}" @class([
                                    'flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-semibold',
                                    'bg-blue-600 text-white' => $page === $products->currentPage(),
                                    'text-slate-500 hover:bg-slate-100' => $page !== $products->currentPage(),
                                ])>{{ $page }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <div class="flex flex-col gap-6 2xl:col-span-4">
                {{-- Simulator bagi hasil --}}
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Simulasi Bagi Hasil</h2>
                            <p class="card-subtitle">Hitung bagian owner dan afiliator sebelum menerbitkan produk</p>
                        </div>
                    </div>
                    <div class="card-body flex flex-col gap-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="sim-price" class="text-sm text-slate-500">Harga jual</label>
                                <span class="tabular text-sm font-bold text-slate-900" x-text="rupiah(sim.price)"></span>
                            </div>
                            <input id="sim-price" type="range" min="20000" max="1000000" step="5000" x-model.number="sim.price" class="h-2 w-full cursor-pointer accent-blue-600">
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="sim-rate" class="text-sm text-slate-500">Komisi afiliator</label>
                                <span class="text-sm font-bold text-slate-900" x-text="sim.rate + '%'"></span>
                            </div>
                            <input id="sim-rate" type="range" min="10" max="80" step="5" x-model.number="sim.rate" class="h-2 w-full cursor-pointer accent-blue-600">
                        </div>

                        <div class="panel">
                            <div class="flex h-2 w-full gap-[2px] overflow-hidden rounded-full">
                                <div class="h-full bg-blue-600 transition-all" :style="`width: ${100 - sim.rate}%`"></div>
                                <div class="h-full bg-emerald-500 transition-all" :style="`width: ${sim.rate}%`"></div>
                            </div>
                            <dl class="tabular mt-4 grid gap-2 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Bagian owner</dt><dd class="font-bold text-blue-600" x-text="rupiah(sim.price * (100 - sim.rate) / 100)"></dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Komisi afiliator</dt><dd class="font-bold text-emerald-600" x-text="rupiah(sim.price * sim.rate / 100)"></dd></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2"><dt class="text-slate-500">Potensi afiliator (100 penjualan)</dt><dd class="font-semibold text-slate-800" x-text="rupiah(sim.price * sim.rate)"></dd></div>
                            </dl>
                        </div>

                        <button type="button" @click="create({ price: sim.price, commission_rate: sim.rate })" class="btn-soft w-full justify-center py-3">Gunakan Skema Ini ke Produk Baru</button>
                    </div>
                </section>

                {{-- Materi promosi --}}
                <section class="card">
                    <div class="card-header items-center">
                        <h2 class="card-title">Materi Promosi</h2>
                        <span class="text-sm text-slate-400">{{ $stats['promo_materials'] }} materi</span>
                    </div>
                    <p class="card-body text-sm text-slate-500">Sediakan gambar, video, dan caption siap pakai agar afiliator lebih cepat berjualan. Kelola lewat tombol <b>Promosi</b> di tabel produk.</p>
                </section>
            </div>
        </div>

        {{-- Antrean pencairan --}}
        <section id="antrean-pencairan" class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Antrean Pencairan Afiliator</h2>
                    <p class="card-subtitle">Pastikan saldo komisi valid sebelum dana ditransfer</p>
                </div>
                <a href="{{ route('admin.withdrawals.index') }}" class="card-link">Proses pencairan</a>
            </div>
            <div class="relative overflow-x-auto">
                <table class="data-table ledger">
                    <thead>
                        <tr>
                            <th>Afiliator</th>
                            <th>Nominal</th>
                            <th>Tujuan Rekening</th>
                            <th>Waktu Pengajuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingWithdrawals as $withdrawal)
                            <tr>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $withdrawal->affiliate->name }}</span>
                                    <span class="block text-[13px] text-slate-400">{{ $withdrawal->affiliate->email }}</span>
                                </td>
                                <td class="tabular whitespace-nowrap font-bold text-blue-600">{{ rupiah($withdrawal->amount) }}</td>
                                <td>
                                    <span class="tabular block text-slate-800">{{ $withdrawal->bank_name }} &bull; {{ $withdrawal->account_number }}</span>
                                    <span class="block text-[13px] text-slate-400">a.n. {{ $withdrawal->account_holder }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="block text-slate-800">{{ $withdrawal->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    <span class="block text-[13px] text-slate-400">{{ $withdrawal->created_at->diffForHumans() }}</span>
                                </td>
                                <td><x-badge :tone="$withdrawal->status->tone()">{{ $withdrawal->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-slate-500">Belum ada pengajuan pencairan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Modal tambah / edit produk --}}
        <x-modal name="product-form" :show="$oldForm !== null" max-width="lg" focusable>
            <form method="POST" :action="action" enctype="multipart/form-data" class="flex flex-col gap-5 p-6 sm:p-7">
                @csrf
                <input type="hidden" name="_form" value="product">
                <input type="hidden" name="_product_id" :value="form._product_id">
                <input type="hidden" name="_has_file" :value="form.has_file ? 1 : 0">
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-900" x-text="mode === 'edit' ? 'Edit Produk' : 'Tambah Produk Digital'"></h3>
                    <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="$dispatch('close-modal', 'product-form')">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="flex flex-col gap-4">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama produk</label>
                        <input id="name" name="name" type="text" x-model="form.name" placeholder="Contoh: Notion Freelance Suite 2025" required class="input">
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="category" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori</label>
                            <input id="category" name="category" type="text" list="category-options" x-model="form.category" placeholder="E-Course, Ebook..." class="input">
                            <datalist id="category-options">
                                @foreach ($categories->merge(['E-Course', 'Ebook', 'Template', 'Software / License Key'])->unique() as $category)
                                    <option value="{{ $category }}">
                                @endforeach
                            </datalist>
                            <x-input-error :messages="$errors->get('category')" class="mt-1" />
                        </div>
                        <div>
                            <label for="price" class="mb-1.5 block text-sm font-semibold text-slate-700">Harga jual (Rp)</label>
                            <input id="price" name="price" type="number" min="1000" step="500" x-model.number="form.price" placeholder="150000" required class="input">
                            <x-input-error :messages="$errors->get('price')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="commission_rate" class="mb-1.5 block text-sm font-semibold text-slate-700">Komisi (%)</label>
                            <input id="commission_rate" name="commission_rate" type="number" min="0" max="100" step="0.5" x-model.number="form.commission_rate" required class="input">
                            <x-input-error :messages="$errors->get('commission_rate')" class="mt-1" />
                        </div>
                        <div>
                            <span class="mb-1.5 block text-sm font-semibold text-slate-700">Komisi afiliator</span>
                            <div class="tabular rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-600"
                                 x-text="rupiah((form.price || 0) * (form.commission_rate || 0) / 100) + ' / unit'"></div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea id="description" name="description" rows="3" x-model="form.description" placeholder="Jelaskan isi dan manfaat produk" class="input"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="thumbnail" class="mb-1.5 block text-sm font-semibold text-slate-700">Thumbnail (maks. 2 MB)</label>
                            <input id="thumbnail" name="thumbnail" type="file" accept="image/*"
                                   class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-600">
                            <x-input-error :messages="$errors->get('thumbnail')" class="mt-1" />
                        </div>
                        <div>
                            <label for="file" class="mb-1.5 block text-sm font-semibold text-slate-700">File produk (maks. 50 MB)</label>
                            <input id="file" name="file" type="file"
                                   class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-600">
                            <p class="mt-1 text-[13px] text-slate-400" x-show="mode === 'edit'"
                               x-text="form.has_file ? 'Sudah ada file. Kosongkan jika tidak diganti.' : 'Belum ada file produk.'"></p>
                            <x-input-error :messages="$errors->get('file')" class="mt-1" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Aktif (tampil di katalog customer &amp; afiliator)</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <div class="flex items-center gap-4">
                        <template x-if="mode === 'edit'">
                            <button type="submit" form="delete-product-form" class="text-sm font-semibold text-red-600 hover:underline"
                                    onclick="return confirm('Hapus produk ini? Produk yang sudah punya transaksi tidak bisa dihapus, nonaktifkan saja.')">Hapus</button>
                        </template>
                        <template x-if="mode === 'edit' && form.has_file">
                            <a :href="config.downloadUrl.replace('__ID__', form._product_id)" class="card-link">Unduh file</a>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="$dispatch('close-modal', 'product-form')" class="btn-outline btn-sm py-2.5">Batal</button>
                        <button class="btn-primary btn-sm py-2.5" x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Terbitkan Produk'"></button>
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
